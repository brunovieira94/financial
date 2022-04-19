<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\BankAccount;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Config;

use App\CNABLayoutsParser\CnabParser\Parser\Layout;
use App\CNABLayoutsParser\CnabParser\Model\Remessa;
use App\CNABLayoutsParser\CnabParser\Output\RemessaFile;

class ItauCNABService
{
    private $paymentRequest;
    private $installments;
    private $company;
    private $withCompany = ['bank_account', 'managers', 'city'];
    private $withPaymentRequest = ['approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];

    public function __construct(PaymentRequest $paymentRequest, Company $company, PaymentRequestHasInstallments $installments)
    {
        $this->paymentRequest = $paymentRequest;
        $this->company = $company;
        $this->installments = $installments;
    }

    public function generateCNAB240Shipping($requestInfo)
    {
        $company = $this->company->with($this->withCompany)->findOrFail($requestInfo['company_id']);
        $bankAccount = BankAccount::with('bank')->findOrFail($requestInfo['bank_account_id']);

        $recipient = new \App\Helpers\Pessoa(
            [
                'nome'      => $company->company_name,
                'endereco'  => $company->address,
                'cep'       => $company->cep,
                'uf'        => $company->city->state->uf,
                'cidade'    => $company->city->title,
                'documento' => $company->cnpj,
                'numero'    => $company->number,
                'complemento' => $company->complement ?? '',
            ]
        );

        $bankData = [
            'agencia'      => $bankAccount->agency_number,
            'conta'        => $bankAccount->account_number,
            'contaDv'      => $bankAccount->account_check_number ?? '',
            'beneficiario' => $recipient,
            'variacaoCarteira'     => '017',
            'convenio'     => $bankAccount->covenant ?? '',
            'codigoFormaPagamento' => $requestInfo['code_cnab'] ?? '',
            'tipoSeguimento' => $requestInfo['group_form_payment_id'],
        ];

        $allPaymentRequest = $this->paymentRequest->with($this->withPaymentRequest)->whereIn('id', $requestInfo['payment_request_ids'])->get();
        $billets = [];

        foreach ($allPaymentRequest as $paymentRequest) {
            $payer = new \App\Helpers\Pessoa(
                [
                    'nome'      => $paymentRequest->provider->provider_type == 'F' ? $paymentRequest->provider->full_name : $paymentRequest->provider->company_name,
                    'endereco'  => $paymentRequest->provider->address,
                    'cep'       => $paymentRequest->provider->cep,
                    'uf'        => $paymentRequest->provider->city->state->title,
                    'cidade'    => $paymentRequest->provider->city,
                    'documento' => $paymentRequest->provider->provider_type == 'F' ? $paymentRequest->provider->cpf : $paymentRequest->provider->cnpj,
                    'numero'    => $paymentRequest->provider->number,
                    'complemento' => $paymentRequest->provider->complement ?? '',
                ]
            );

            foreach ($paymentRequest->installments as $installment) {
                if ($installment->codBank != 'BD') {
                    $billet =
                        [
                            'dataVencimento'         => new Carbon($installment->due_date),
                            'valor'                  => $installment->portion_amount ?? $paymentRequest->amount,
                            'transferTypeIdentification' => $paymentRequest->bank_account_provider->account_type ?? 3, // 3 - Pix
                            'numeroDocumento'        => $installment->id,
                            'pagador'                => $payer,
                            'beneficiario'           => $recipient,
                            'agencia'                => $paymentRequest->bank_account_provider->agency_number ?? 0,
                            'conta'                  => $paymentRequest->bank_account_provider->account_number ?? 0,
                            'contaDv'                => $paymentRequest->bank_account_provider->account_check_number ?? 0,
                            'codigoDeBarra'          => $paymentRequest->bar_code,
                            'desconto'               => 0,
                            'multa '                 => 0,
                            'dataPagamento'          => new Carbon($paymentRequest->pay_date),
                            'valorPagamento'         => $paymentRequest->amount,
                            'tipoDocumento'          => $paymentRequest->payment_type,
                            'convenio'               => '1111', //Validar
                            'agenciaDv'              => $paymentRequest->bank_account_provider->agency_check_number ?? '',
                        ];

                    switch ($bankAccount->bank->bank_code) {
                        case '341':
                            $billetData = new \App\Helpers\Boleto\Banco\Itau($billet);
                            break;
                        case '001':
                            $billetData = new \App\Helpers\Boleto\Banco\Bb($billet);
                            break;
                        default:
                            return Response('Não é possível gerar CNAB com este código bancário.', 422);
                    }
                    array_push($billets, $billetData);
                }
            };
        }

        switch ($bankAccount->bank->bank_code) {
            case '341':
                $bankData['carteira'] = 112;
                $shipping = new \App\Helpers\Cnab\Remessa\Cnab240\Banco\Itau($bankData);
                break;
            case '001':
                $bankData['carteira'] = 11;
                $shipping = new \App\Helpers\Cnab\Remessa\Cnab240\Banco\Bb($bankData);
                break;
            default:
                return Response('Não é possível gerar CNAB com este código bancário.', 422);
        }

        $shipping->addBoletos($billets);
        $shipping->save();

        DB::table('accounts_payable_approval_flows')
            ->whereIn('payment_request_id', $requestInfo['payment_request_ids'])
            ->update(
                array(
                    'status' => Config::get('constants.status.cnab generated')
                )
            );


        return response()->json([
            'linkArchive' => Storage::disk('s3')->temporaryUrl('tempCNAB/cnab-remessa.txt', now()->addMinutes(5))
        ], 200);

        //$filename = 'cnab.txt';
        //$tempImage = tempnam(sys_get_temp_dir(), $filename);
        //copy(, $tempImage);
        //return response()->download($tempImage, $filename);
    }

    public function receiveCNAB240($requestInfo)
    {

        $returnFile = $requestInfo->file('return-file');

        $processArchive = new \App\Helpers\Cnab\Retorno\Cnab240\Banco\Itau($returnFile);
        $processArchive->processar();

        $returnArray = $processArchive->getDetalhes();

        foreach ($returnArray as $batch) {
            $installments =  $this->installments->findOrFail($batch->numeroDocumento);
            $installments->status = $batch->ocorrencia;
            $installments->codBank = $batch->nossoNumero;
            $installments->amount_received = $batch->valorRecebido;
            $installments->save();
        }

        $idParcelsAlreadyVerified = [];
        $idUnpaidPayment = [];
        $idPaidPayment = [];
        $idPaymentFinished = [];

        foreach ($returnArray as $batch) {

            if (!in_array($batch->numeroDocumento, $idParcelsAlreadyVerified)) {
                $paymentRequest = PaymentRequest::with('installments')
                    ->whereRelation('installments', 'id', '=', $batch->numeroDocumento)
                    ->first();

                $thePaymentHasBeenPaid = true;

                foreach ($paymentRequest->installments as $installment) {
                    array_push($idParcelsAlreadyVerified, $installment->id);

                    if ($installment->status != 'BD') {
                        $thePaymentHasBeenPaid = false;
                        if (!in_array($paymentRequest->id, $idUnpaidPayment)) {
                            array_push($idUnpaidPayment, $paymentRequest->id);
                        }
                    }
                }

                if ($thePaymentHasBeenPaid == true) {
                    if ($paymentRequest->payment_type == 0) {
                        array_push($idPaymentFinished, $paymentRequest->id);
                    } else {
                        array_push($idPaidPayment, $paymentRequest->id);
                    }
                }
            }
        }

        AccountsPayableApprovalFlow::whereIn('payment_request_id', $idPaidPayment)
            ->update(['status' => Config::get('constants.status.paid out')]);

        AccountsPayableApprovalFlow::whereIn('payment_request_id', $idPaymentFinished)
            ->update(['status' => Config::get('constants.status.finished')]);

        AccountsPayableApprovalFlow::whereIn('payment_request_id', $idUnpaidPayment)
            ->update(['status' => Config::get('constants.status.approved')]);

        return response()->json([
            'Sucesso' => 'Processo finalizado.',
        ], 200);
    }


    public function cnabParse($requestInfo)
    {
        $remessaLayout = new Layout( app_path() . '/CNABLayoutsParser/config/itau/cnab240/cobranca.yml');
        $remessa = new Remessa($remessaLayout);

        // header arquivo
        $remessa->header->codigo_banco = 341;
        $remessa->header->tipo_inscricao = 2;
        $remessa->header->inscricao_numero = '12234567000186';
        $remessa->header->agencia = 2932;
        $remessa->header->conta = 24992;
        $remessa->header->dac = 9;
        $remessa->header->nome_empresa = 'NOME DA EMPRESA';
        $remessa->header->data_geracao = date('dmY');
        $remessa->header->hora_geracao = date('His');
        $remessa->header->numero_sequencial_arquivo_retorno = 1;

        // criar um novo lote de serviço para a remessa
        // informando o código sequencial do lote
        $lote = $remessa->novoLote(1);

        $lote->header->codigo_banco = 341;
        $lote->header->lote_servico = $lote->sequencial;
        $lote->header->tipo_registro = 1;
        $lote->header->tipo_operacao = 'R';
        $lote->header->tipo_servico = '01';
        $lote->header->zeros_01 = 0;
        $lote->header->versao_layout_lote = '030';
        $lote->header->brancos_01 = '';
        $lote->header->tipo_inscricao = 2;
        $lote->header->inscricao_empresa = '12234567000186';
        $lote->header->brancos_02 = '0';
        $lote->header->zeros_02 = 0;
        $lote->header->agencia = 2932;
        $lote->header->brancos_03 = '';
        $lote->header->zeros_03 = 0;
        $lote->header->conta = '24992';
        $lote->header->brancos_04 = '';
        $lote->header->dac = 9;
        $lote->header->nome_empresa = 'NOME DA EMPRESA';
        $lote->header->brancos_05 = '';
        $lote->header->numero_sequencial_arquivo_retorno = 1;
        $lote->header->data_gravacao = date('dmY');
        $lote->header->data_credito = date('dmY');
        $lote->header->brancos_06 = '';

        $detalhe = $lote->novoDetalhe();
        // segmento p
        $detalhe->segmento_p->lote_servico = $lote->sequencial;
        $detalhe->segmento_p->nummero_sequencial_registro_lote = 1;
        $detalhe->segmento_p->codigo_ocorrencia = '01';
        $detalhe->segmento_p->agencia = 2932;
        $detalhe->segmento_p->conta = 24992;
        $detalhe->segmento_p->dac = 9;
        $detalhe->segmento_p->carteira = 109;
        $detalhe->segmento_p->nosso_numero = 12345678;
        $detalhe->segmento_p->dac_nosso_numero = 3;
        $detalhe->segmento_p->numero_documento = 1;
        $detalhe->segmento_p->vencimento = '10052016';
        $detalhe->segmento_p->valor_titulo = 1000;
        $detalhe->segmento_p->agencia_cobradora = 0;
        $detalhe->segmento_p->dac_agencia_cobradora = 0;
        $detalhe->segmento_p->especie = '05';
        $detalhe->segmento_p->aceite = 'N';
        $detalhe->segmento_p->data_emissao = date('dmY');
        $detalhe->segmento_p->data_juros_mora = '11052016';
        $detalhe->segmento_p->juros_1_dia = 0;
        $detalhe->segmento_p->data_1o_desconto = '00000000';
        $detalhe->segmento_p->valor_1o_desconto = 0;
        $detalhe->segmento_p->valor_iof = 38;
        $detalhe->segmento_p->valor_abatimento = 0;
        $detalhe->segmento_p->identificacao_titulo_empresa = '';
        $detalhe->segmento_p->codigo_negativacao_protesto = 0;
        $detalhe->segmento_p->prazo_negativacao_protesto = 0;
        $detalhe->segmento_p->codigo_baixa = 0;
        $detalhe->segmento_p->prazo_baixa = 0;
        // segmento q
        $detalhe->segmento_q->lote_servico = $lote->sequencial;
        $detalhe->segmento_q->nummero_sequencial_registro_lote = 2;
        $detalhe->segmento_q->codigo_ocorrencia = '01';
        $detalhe->segmento_q->tipo_inscricao = 2;
        $detalhe->segmento_q->inscricao_numero = '12345678999';
        $detalhe->segmento_q->nome_pagador = 'NOME PAGADOR';
        $detalhe->segmento_q->logradouro = 'RUA PAGADOR';
        $detalhe->segmento_q->bairro = 'BAIRRO';
        $detalhe->segmento_q->cep = 31814;
        $detalhe->segmento_q->sufixo_cep = 500;
        $detalhe->segmento_q->cidade = 'CIDADE';
        $detalhe->segmento_q->uf = 'MG';
        $detalhe->segmento_q->tipo_inscricao_sacador = 2;
        $detalhe->segmento_q->inscricao_sacador = '12234567000186';
        $detalhe->segmento_q->nome_sacador = 'NOME DA EMPRESA';
        // segmento r opcional nao adicionado
        unset($detalhe->segmento_r);
        // segmento y opcional nao adicionado
        unset($detalhe->segmento_y);
        // insere o detalhe no lote da remessa
        $lote->inserirDetalhe($detalhe);

        // trailer lote
        $lote->trailer->lote_servico = $lote->sequencial;
        $lote->trailer->quantidade_registros_lote = 4; // quantidade de Registros do Lote correspondente à soma da quantidade dos registros tipo 1 (header_lote), 3(detalhes) e 5(trailer_lote)
        $lote->trailer->quantidade_cobranca_simples = 1;
        $lote->trailer->valor_total_cobranca_simples = 10000;
        $lote->trailer->quantidade_cobranca_vinculada = 0;
        $lote->trailer->valor_total_cobranca_vinculada = 0;
        $lote->trailer->aviso_bancario = '00000000';
        // inserir lote na remessa
        $remessa->inserirLote($lote);

        $lote = $remessa->novoLote(2);

        $lote->header->codigo_banco = 341;
        $lote->header->lote_servico = $lote->sequencial;
        $lote->header->tipo_registro = 1;
        $lote->header->tipo_operacao = 'R';
        $lote->header->tipo_servico = '01';
        $lote->header->zeros_01 = 0;
        $lote->header->versao_layout_lote = '030';
        $lote->header->brancos_01 = '';
        $lote->header->tipo_inscricao = 2;
        $lote->header->inscricao_empresa = '12234567000186';
        $lote->header->brancos_02 = '0';
        $lote->header->zeros_02 = 0;
        $lote->header->agencia = 2932;
        $lote->header->brancos_03 = '';
        $lote->header->zeros_03 = 0;
        $lote->header->conta = '24992';
        $lote->header->brancos_04 = '';
        $lote->header->dac = 9;
        $lote->header->nome_empresa = 'NOME DA EMPRESA';
        $lote->header->brancos_05 = '';
        $lote->header->numero_sequencial_arquivo_retorno = 1;
        $lote->header->data_gravacao = date('dmY');
        $lote->header->data_credito = date('dmY');
        $lote->header->brancos_06 = '';

        $detalhe = $lote->novoDetalhe();
        // segmento p
        $detalhe->segmento_p->lote_servico = $lote->sequencial;
        $detalhe->segmento_p->nummero_sequencial_registro_lote = 1;
        $detalhe->segmento_p->codigo_ocorrencia = '01';
        $detalhe->segmento_p->agencia = 2932;
        $detalhe->segmento_p->conta = 24992;
        $detalhe->segmento_p->dac = 9;
        $detalhe->segmento_p->carteira = 109;
        $detalhe->segmento_p->nosso_numero = 12345678;
        $detalhe->segmento_p->dac_nosso_numero = 3;
        $detalhe->segmento_p->numero_documento = 1;
        $detalhe->segmento_p->vencimento = '10052016';
        $detalhe->segmento_p->valor_titulo = 1000;
        $detalhe->segmento_p->agencia_cobradora = 0;
        $detalhe->segmento_p->dac_agencia_cobradora = 0;
        $detalhe->segmento_p->especie = '05';
        $detalhe->segmento_p->aceite = 'N';
        $detalhe->segmento_p->data_emissao = date('dmY');
        $detalhe->segmento_p->data_juros_mora = '11052016';
        $detalhe->segmento_p->juros_1_dia = 0;
        $detalhe->segmento_p->data_1o_desconto = '00000000';
        $detalhe->segmento_p->valor_1o_desconto = 0;
        $detalhe->segmento_p->valor_iof = 38;
        $detalhe->segmento_p->valor_abatimento = 0;
        $detalhe->segmento_p->identificacao_titulo_empresa = '';
        $detalhe->segmento_p->codigo_negativacao_protesto = 0;
        $detalhe->segmento_p->prazo_negativacao_protesto = 0;
        $detalhe->segmento_p->codigo_baixa = 0;
        $detalhe->segmento_p->prazo_baixa = 0;
        // segmento q
        $detalhe->segmento_q->lote_servico = $lote->sequencial;
        $detalhe->segmento_q->nummero_sequencial_registro_lote = 2;
        $detalhe->segmento_q->codigo_ocorrencia = '01';
        $detalhe->segmento_q->tipo_inscricao = 2;
        $detalhe->segmento_q->inscricao_numero = '12345678999';
        $detalhe->segmento_q->nome_pagador = 'NOME PAGADOR';
        $detalhe->segmento_q->logradouro = 'RUA PAGADOR';
        $detalhe->segmento_q->bairro = 'BAIRRO';
        $detalhe->segmento_q->cep = 31814;
        $detalhe->segmento_q->sufixo_cep = 500;
        $detalhe->segmento_q->cidade = 'CIDADE';
        $detalhe->segmento_q->uf = 'MG';
        $detalhe->segmento_q->tipo_inscricao_sacador = 2;
        $detalhe->segmento_q->inscricao_sacador = '12234567000186';
        $detalhe->segmento_q->nome_sacador = 'NOME DA EMPRESA';
        // segmento r opcional nao adicionado
        unset($detalhe->segmento_r);
        // segmento y opcional nao adicionado
        unset($detalhe->segmento_y);
        // insere o detalhe no lote da remessa
        $lote->inserirDetalhe($detalhe);

        // trailer lote
        $lote->trailer->lote_servico = $lote->sequencial;
        $lote->trailer->quantidade_registros_lote = 4; // quantidade de Registros do Lote correspondente à soma da quantidade dos registros tipo 1 (header_lote), 3(detalhes) e 5(trailer_lote)
        $lote->trailer->quantidade_cobranca_simples = 1;
        $lote->trailer->valor_total_cobranca_simples = 10000;
        $lote->trailer->quantidade_cobranca_vinculada = 0;
        $lote->trailer->valor_total_cobranca_vinculada = 0;
        $lote->trailer->aviso_bancario = '00000000';
        // inserir lote na remessa
        $remessa->inserirLote($lote);

        // trailer arquivo
        $remessa->trailer->total_lotes = 1; // quantidade de Lotes do arquivo correspondente à soma da quantidade dos registros tipo 1 (header_lote).
        $remessa->trailer->total_registros = 6; //total da quantidade de Registros no arquivo correspondente à soma da quantidade dos registros tipo 0(header_arquivo), 1(header_lote), 3(detalhes), 5(trailer_lote) e 9(trailer_arquivo).

        // gera arquivo
        $remessaFile = new RemessaFile($remessa);
        $remessaFile->generate(app_path() . 'CNABLayoutsParser/tests/out/itaucobranca240.rem');

        return response()->json([
            'linkArchive' => Storage::disk('s3')->temporaryUrl('tempCNAB/cnab-remessa.txt', now()->addMinutes(5))
        ], 200);
    }
}

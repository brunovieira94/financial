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
use App\CNABLayoutsParser\CnabParser\Util;

class ItauCNABService
{
    private $paymentRequest;
    private $installments;
    private $company;
    private $withCompany = ['bank_account', 'managers', 'city'];
    private $withPaymentRequest = ['group_payment', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user'];

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

        $company = $this->company->with($this->withCompany)->findOrFail($requestInfo['company_id']);
        $bankAccount = BankAccount::with('bank')->findOrFail($requestInfo['bank_account_id']);

        $remessaLayout = new Layout(app_path() . '/CNABLayoutsParser/config/bb/cnab240/cobranca.yml');
        $remessa = new Remessa($remessaLayout);

        // header arquivo
        $remessa->header->codigo_banco = $bankAccount->bank->bank_code;
        $remessa->header->tipo_inscricao = 2; // CNPJ
        $remessa->header->inscricao_numero = Util::onlyNumbers($company->cnpj);
        $remessa->header->numero_convenio = $bankAccount->covenant;
        $remessa->header->agencia = $bankAccount->agency_number;
        $remessa->header->digito_verificador_agencia = $bankAccount->agency_check_number;
        $remessa->header->conta = $bankAccount->account_number;
        $remessa->header->digito_verificador_conta = $bankAccount->account_check_number;
        $remessa->header->dac = 9;
        $remessa->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
        $remessa->header->data_geracao = date('dmY');
        $remessa->header->hora_geracao = date('His');
        $remessa->header->numero_sequencial_arquivo_retorno = 1;

        //obter todos pagamentos

        $allGroupedPaymentRequest = Utils::groupPayments(
            $this->paymentRequest
                ->with($this->withPaymentRequest)
                ->whereIn('id', $requestInfo['payment_request_ids'])
                ->get(),
            $bankAccount->bank->bank_code
        );

        $lotQuantity = 0;
        $sumDetails = 0;

        foreach ($allGroupedPaymentRequest as $key => $groupedPaymentRequest) {

            $lotQuantity += 1;

            $lotQuantityDetails = 0;
            $lotValue = 0;

            $lote = $remessa->novoLote($lotQuantity);

            $lote->header->agencia = $bankAccount->agency_number;
            $lote->header->digito_verificador_agencia = $bankAccount->agency_check_number;
            $lote->header->conta = $bankAccount->account_number;
            $lote->header->digito_verificador_conta = $bankAccount->account_check_number;
            $lote->header->numero_convenio = $bankAccount->covenant;
            $lote->header->codigo_banco = $bankAccount->bank->bank_code;
            $lote->header->lote_servico = $lote->sequencial;
            $lote->header->tipo_registro = 1;
            $lote->header->tipo_operacao = 'C';
            $lote->header->tipo_servico = '98';
            $lote->header->inscricao_numero = Util::onlyNumbers($company->cnpj);
            $lote->header->numero_convenio = $bankAccount->covenant;
            $lote->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
            $lote->header->tipo_inscricao = 2;
            $lote->header->data_gravacao = date('dmY');
            $lote->header->data_credito = date('dmY');
            $lote->header->forma_lancamento  = $key;

            foreach ($groupedPaymentRequest as $paymentRequest) {
                foreach ($paymentRequest->installments as $installment) {

                    $detalhe = $lote->novoDetalhe();

                    $lotQuantityDetails += 1;
                    $lotValue += $installment->portion_amount;

                    if ($paymentRequest->group_form_payment_id == 1)
                    {
                        $detalhe->segmento_j->lote_servico = $lote->sequencial;
                        $detalhe->segmento_j->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_j->codigo_barras = Util::codigoBarrasBB($paymentRequest->bar_code);
                        $detalhe->segmento_j->nome_beneficiario = Utils::formatCnab('X', $paymentRequest->provider->provider_type == 'J' ? $paymentRequest->provider->company_name : $paymentRequest->provider->full_name, '30');
                        $dataVencimento = new Carbon($installment->due_date); // data vendimento
                        $detalhe->segmento_j->vencimento = $dataVencimento->format('dmY');
                        $detalhe->segmento_j->valor = Utils::formatCnab('9', number_format($installment->portion_amount, 2), 15);
                        $dataPagamento = new Carbon($paymentRequest->pay_date);
                        $detalhe->segmento_j->data_pagamento = $dataPagamento;
                        $detalhe->segmento_j->valor_pagamento = Utils::formatCnab('9', number_format($installment->portion_amount, 2), 15);
                        $detalhe->segmento_j->identificacao_titulo_empresa = $installment->id; //id parcela;

                        //segmento j52 detalhe
                        $lotQuantityDetails += 1;
                        $detalhe->segmento_j52->lote_servico = $lotQuantity;
                        $detalhe->segmento_j52->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_j52->numero_inscricao_pagador = Utils::onlyNumbers($company->cnpj);
                        $detalhe->segmento_j52->tipo_inscricao_beneficiario = strlen(Utils::onlyNumbers($paymentRequest->provider->cnpj)) == 14 ? 2 : 1;
                        $detalhe->segmento_j52->numero_inscricao_beneficiario = $paymentRequest->provider->provider_type == 'J' ? Utils::onlyNumbers($paymentRequest->provider->cnpj) : Utils::onlyNumbers($paymentRequest->provider->cpf);
                        unset($detalhe->segmento_p);

                        $lote->inserirDetalhe($detalhe);
                    }else
                    {

                    }
                }
            }

            $lotQuantityDetails + 2;
            $lote->trailer->lote_servico = $lote->sequencial;
            $lote->trailer->quantidade_registros_lote = $lotQuantityDetails; // quantidade de Registros do Lote correspondente à soma da quantidade dos registros tipo 1 (header_lote), 3(detalhes) e 5(trailer_lote)
            $lote->trailer->quantidade_cobranca_simples = 1;
            //$lote->trailer->valor_total_cobranca_simples = ;
            $lote->trailer->quantidade_cobranca_vinculada = 0;
            $lote->trailer->valor_total_cobranca_vinculada = 0;
            $lote->trailer->aviso_bancario = '00000000';
            $lote->trailer->somatoria_lote = Utils::formatCnab('9', number_format($lotValue, 2), 15);

            $sumDetails +=  $lotQuantityDetails; //somar todos registros

            $remessa->inserirLote($lote);
        }

        $remessa->trailer->total_lotes = $lotQuantity;
        $remessa->trailer->total_registros = $sumDetails;

        // gera arquivo
        $remessaFile = new RemessaFile($remessa);
        $remessaFile->generate(app_path() . 'CNABLayoutsParser/tests/out/bbcobranca240.rem');

        return response()->json([
            'linkArchive' => Storage::disk('s3')->temporaryUrl('tempCNAB/cnab-remessa.txt', now()->addMinutes(5))
        ], 200);
    }
}

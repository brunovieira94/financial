<?php

namespace App\CNABLayoutsParser\CnabParser\Remessa;

use App\Services\Utils;
use Carbon\Carbon;

class Remessa
{
    public static function gerarRemessaBancoBrasil($remessa, $company, $bankAccount, $allGroupedInstallment, $installmentsIds, $headerDate, $headerTime)
    {
        $remessa->header->codigo_banco = Utils::formatCnab('9', $bankAccount->bank->bank_code, 3);
        $remessa->header->tipo_inscricao = 2; // CNPJ
        $remessa->header->inscricao_numero = Utils::formatCnab('9', Utils::onlyNumbers($company->cnpj), 14);
        $remessa->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
        $remessa->header->agencia = Utils::formatCnab('9', $bankAccount->agency_number, 5);
        $remessa->header->digito_verificador_agencia = Utils::formatCnab('X', $bankAccount->agency_check_number, 1);
        $remessa->header->conta = Utils::formatCnab('9', $bankAccount->account_number, 12);
        $remessa->header->digito_verificador_conta = Utils::formatCnab('X', $bankAccount->account_check_number, 1);
        $remessa->header->dac = 9;
        $remessa->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
        $remessa->header->data_geracao = $headerDate;
        $remessa->header->hora_geracao = $headerTime;
        //$remessa->header->numero_sequencial_arquivo_retorno = 1;

        $lotQuantity = 0;
        $sumDetails = 0;

        foreach ($allGroupedInstallment as $key => $groupedInstallment) {

            $lotQuantity += 1;

            $lotQuantityDetails = 0;
            $lotValue = 0;

            $lote = $remessa->novoLote($lotQuantity);

            $lote->header->agencia = $bankAccount->agency_number;
            $lote->header->digito_verificador_agencia = $bankAccount->agency_check_number ?? '';
            $lote->header->conta = $bankAccount->account_number;
            $lote->header->digito_verificador_conta = $bankAccount->account_check_number ?? '';
            $lote->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
            $lote->header->codigo_banco = $bankAccount->bank->bank_code;
            $lote->header->lote_servico = $lote->sequencial;
            $lote->header->tipo_registro = 1;
            $lote->header->tipo_operacao = 'C';
            $lote->header->tipo_servico = '98';
            $lote->header->inscricao_numero = Utils::onlyNumbers($company->cnpj);
            $lote->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
            $lote->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
            $lote->header->tipo_inscricao = 2;
            $lote->header->data_gravacao = date('dmY');
            $lote->header->data_credito = date('dmY');
            $lote->header->forma_lancamento  = $key;

            foreach ($groupedInstallment as $installment) {
                $detalhe = $lote->novoDetalhe();

                $amount = ($installment->initial_value + $installment->fees + $installment->fine) - $installment->discount;
                $lotValue += $amount;

                $dataBankInstallment = Utils::getBankDetailsCnab($installment);
                $nomeBeneficiario = $dataBankInstallment['nomeBeneficiario'];
                $inscricao = $dataBankInstallment['inscricao'];

                if ($installment->group_form_payment_id == 1 && $installment->bar_code != null) {
                    if ($installment->type_billet == 4) {
                        $lotQuantityDetails++;
                        $detalhe->segmento_o->lote_servico = $lote->sequencial;
                        $detalhe->segmento_o->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_o->codigo_barras = Utils::codigoBarrasBB(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_o->nome_beneficiario = Utils::formatCnab('X',  $nomeBeneficiario, '30');
                        $dataVencimento = new Carbon($installment->extension_date); // data vendimento
                        $detalhe->segmento_o->vencimento = $dataVencimento->format('dmY');
                        $detalhe->segmento_o->valor = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $dataPagamento = new Carbon($installment->extension_date); // validar
                        $detalhe->segmento_o->data_pagamento = $dataPagamento->format('dmY');
                        $detalhe->segmento_o->valor_pagamento = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $detalhe->segmento_o->identificacao_titulo_empresa = $installment->id; //id parcela;

                        unset($detalhe->segmento_a);
                        unset($detalhe->segmento_b);
                        unset($detalhe->segmento_j);
                        unset($detalhe->segmento_j52);

                        $lote->inserirDetalhe($detalhe);
                    } else {
                        $lotQuantityDetails++;
                        $detalhe->segmento_j->lote_servico = $lote->sequencial;
                        $detalhe->segmento_j->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_j->codigo_barras = Utils::codigoBarrasBB(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_j->codigo_barras = Utils::codigoBarrasBB(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_j->nome_beneficiario = Utils::formatCnab('X',  $nomeBeneficiario, '30');
                        $dataVencimento = new Carbon($installment->extension_date); // data vendimento
                        $detalhe->segmento_j->vencimento = $dataVencimento->format('dmY');
                        $detalhe->segmento_j->valor = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $dataPagamento = new Carbon($installment->extension_date); // validar
                        $detalhe->segmento_j->data_pagamento = $dataPagamento->format('dmY');
                        $detalhe->segmento_j->valor_pagamento = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $detalhe->segmento_j->identificacao_titulo_empresa = $installment->id; //id parcela;

                        //segmento j52 detalhe
                        $lotQuantityDetails++;
                        $detalhe->segmento_j52->lote_servico = $lotQuantity;
                        $detalhe->segmento_j52->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_j52->numero_inscricao_pagador = Utils::onlyNumbers($company->cnpj);
                        $detalhe->segmento_j52->tipo_inscricao_beneficiario = strlen(Utils::onlyNumbers($inscricao)) == 14 ? 2 : 1;
                        $detalhe->segmento_j52->numero_inscricao_beneficiario = Utils::onlyNumbers($inscricao);
                        unset($detalhe->segmento_a);
                        unset($detalhe->segmento_b);
                        unset($detalhe->segmento_o);
                        $lote->inserirDetalhe($detalhe);
                    }
                } else {

                    $lotQuantityDetails++;
                    $lote->header->tipo_servico = '20';

                    //detalhes do seguimento A
                    $detalhe->segmento_a->codigo_camara_centralizadora = Utils::centralizadoraBB($key);
                    $detalhe->segmento_a->lote_servico = $lotQuantity;
                    $detalhe->segmento_a->numero_registro = $lotQuantityDetails;
                    $detalhe->segmento_a->codigo_banco_favorecido = $installment->bank_account_provider->bank->bank_code;
                    $detalhe->segmento_a->agencia_favorecido = $installment->bank_account_provider->agency_number;
                    $detalhe->segmento_a->verificador_agencia_favorecido = $installment->bank_account_provider->agency_check_number ?? '';
                    $detalhe->segmento_a->conta_favorecido = $installment->bank_account_provider->account_number;
                    $detalhe->segmento_a->verificador_conta_favorecido = $installment->bank_account_provider->account_check_number ?? '';
                    $detalhe->segmento_a->nome_favorecido = Utils::formatCnab('X', $nomeBeneficiario, '30');
                    $detalhe->segmento_a->n_docto_atribuido_empresa = $installment->id;
                    $dataPagamento = new Carbon($installment->extension_date);
                    $detalhe->segmento_a->data_pagamento = $dataPagamento->format('dmY');
                    $detalhe->segmento_a->valor_pagamento = Utils::formatCnab('9', number_format($amount, 2), 15);

                    //detalhes do seguimento B
                    $lotQuantityDetails++;
                    $detalhe->segmento_b->lote_servico = $lotQuantity;
                    $detalhe->segmento_b->numero_registro = $lotQuantityDetails;
                    $detalhe->segmento_b->tipo_inscricao_favorecido = strlen(Utils::onlyNumbers($inscricao)) == 14 ? 2 : 1;
                    $detalhe->segmento_b->inscricao_favorecido = Utils::onlyNumbers($inscricao);
                    $dataVencimento = new Carbon($installment->extension_date); // data vendimento
                    $detalhe->segmento_b->vencimento = $dataVencimento->format('dmY');

                    unset($detalhe->segmento_j);
                    unset($detalhe->segmento_j52);
                    unset($detalhe->segmento_o);
                    $lote->inserirDetalhe($detalhe);
                }
            }

            $lotQuantityDetails + 2;
            $lote->trailer->lote_servico = $lote->sequencial;
            $lote->trailer->quantidade_registros_lote = $lotQuantityDetails + 2; // quantidade de Registros do Lote correspondente à soma da quantidade dos registros tipo 1 (header_lote), 3(detalhes) e 5(trailer_lote)
            $lote->trailer->quantidade_cobranca_simples = 1;
            $lote->trailer->quantidade_cobranca_vinculada = 0;
            $lote->trailer->valor_total_cobranca_vinculada = 0;
            $lote->trailer->aviso_bancario = '00000000';
            $lote->trailer->somatoria_lote = Utils::formatCnab('9', number_format($lotValue, 2), 15);

            $sumDetails +=  $lotQuantityDetails; //somar todos registros

            $remessa->inserirLote($lote);
        }


        $remessa->trailer->total_lotes = $lotQuantity;
        $remessa->trailer->total_registros = $sumDetails + ($lotQuantity * 2) + 2;

        return $remessa;
    }

    public static function gerarRemessaItau($remessa, $company, $bankAccount, $allGroupedInstallment, $installmentsIds, $headerDate, $headerTime)
    {
        $remessa->header->codigo_banco = $bankAccount->bank->bank_code;
        $remessa->header->tipo_inscricao = 2; // CNPJ
        $remessa->header->inscricao_numero = Utils::onlyNumbers($company->cnpj);
        $remessa->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
        $remessa->header->agencia = $bankAccount->agency_number;
        $remessa->header->digito_verificador_agencia = $bankAccount->agency_check_number;
        $remessa->header->conta = $bankAccount->account_number;
        $remessa->header->digito_verificador_conta = $bankAccount->account_check_number;
        $remessa->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
        $remessa->header->data_geracao = $headerDate;
        $remessa->header->hora_geracao = $headerTime;
        $remessa->header->numero_sequencial_arquivo_retorno = 1;

        $lotQuantity = 0;
        $sumDetails = 0;

        foreach ($allGroupedInstallment as $key => $groupedInstallment) {

            $lotQuantity += 1;

            $lotQuantityDetails = 0;
            $lotValue = 0;

            $lote = $remessa->novoLote($lotQuantity);

            $lote->header->agencia = $bankAccount->agency_number;
            $lote->header->digito_verificador_agencia = $bankAccount->agency_check_number;
            $lote->header->conta = $bankAccount->account_number;
            $lote->header->digito_verificador_conta = $bankAccount->account_check_number;
            $lote->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
            $lote->header->codigo_banco = $bankAccount->bank->bank_code;
            $lote->header->lote_servico = $lote->sequencial;
            $lote->header->tipo_registro = 1;
            $lote->header->tipo_operacao = 'C';
            $lote->header->tipo_servico = '98';
            $lote->header->inscricao_numero = Utils::onlyNumbers($company->cnpj);
            $lote->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
            $lote->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
            $lote->header->tipo_inscricao = 2;
            $lote->header->data_gravacao = date('dmY');
            $lote->header->data_credito = date('dmY');
            $lote->header->forma_pagamento  = $key;
            $lote->header->endereco = Utils::formatCnab('X', $company->address, 30);
            $lote->header->numero = Utils::formatCnab('9', Utils::onlyNumbers($company->number) == '' ? 0 : Utils::onlyNumbers($company->number), 5);
            $lote->header->complemento = Utils::formatCnab('X', $company->complement, 15);
            $lote->header->cidade = Utils::formatCnab('X', $company->city->title, 15);
            $lote->header->cep = Utils::onlyNumbers($company->cep);
            $lote->header->uf = Utils::formatCnab('X', $company->city->state->uf, 2);

            foreach ($groupedInstallment as $installment) {
                $amount = ($installment->initial_value + $installment->fees + $installment->fine) - $installment->discount;

                $dataBankInstallment = Utils::getBankDetailsCnab($installment);
                $nomeBeneficiario = $dataBankInstallment['nomeBeneficiario'];
                $inscricao = $dataBankInstallment['inscricao'];

                $detalhe = $lote->novoDetalhe();

                $lotValue += $amount;

                if ($installment->group_form_payment_id == 1 && $installment->bar_code != null) {
                    if ($installment->type_billet == 4) {
                        $lotQuantityDetails++;
                        $detalhe->segmento_o->lote_servico = 1;
                        $detalhe->segmento_o->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_o->codigo_barras = Utils::codigoBarrasBB(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_o->nome_beneficiario = Utils::formatCnab('X',  $nomeBeneficiario, '30');
                        $dataVencimento = new Carbon($installment->extension_date); // data vendimento
                        $detalhe->segmento_o->vencimento = $dataVencimento->format('dmY');
                        $detalhe->segmento_o->valor = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $dataPagamento = new Carbon($installment->extension_date); // validar
                        $detalhe->segmento_o->data_pagamento = $dataPagamento->format('dmY');
                        $detalhe->segmento_o->valor_pagamento = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $detalhe->segmento_o->identificacao_titulo_empresa = $installment->id; //id parcela;
                        unset($detalhe->segmento_a);
                        unset($detalhe->segmento_b);
                        unset($detalhe->segmento_j);
                        unset($detalhe->segmento_j52);
                        unset($detalhe->segmento_bpix);

                        $lote->inserirDetalhe($detalhe);
                    } else {
                        $lotQuantityDetails++;
                        $detalhe->segmento_j->lote_servico = $lote->sequencial;
                        $detalhe->segmento_j->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_j->nome_beneficiario = Utils::formatCnab('X', $nomeBeneficiario, '30');
                        $dataVencimento = new Carbon($installment->extension_date); // data vendimento
                        $detalhe->segmento_j->vencimento = $dataVencimento->format('dmY');
                        $detalhe->segmento_j->valor = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $dataPagamento = new Carbon($installment->extension_date); //VALIDAR
                        $detalhe->segmento_j->data_pagamento = $dataPagamento->format('dmY');
                        $detalhe->segmento_j->valor_pagamento = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $detalhe->segmento_j->identificacao_titulo_empresa = $installment->id; //id parcela;
                        $detalhe->segmento_j->boleto_banco_favorecido = Utils::codigoBancoFavorecidoBoleto(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_j->boleto_dv_favorecido = Utils::dvBoleto(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_j->boleto_vencimento_favorecido = Utils::fatorVencimentoBoleto(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_j->boleto_moeda_favorecido = Utils::codigoMoedaBoleto(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_j->boleto_valor_favorecido = Utils::valorBoleto(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_j->boleto_campo_livre_favorecido = Utils::campoLivreBoleto(Utils::onlyNumbers($installment->bar_code));

                        //segmento j52 detalhe
                        //$lotQuantityDetails++;
                        $detalhe->segmento_j52->lote_servico = $lotQuantity;
                        $detalhe->segmento_j52->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_j52->numero_inscricao_pagador = Utils::onlyNumbers($company->cnpj);
                        $detalhe->segmento_j52->tipo_inscricao_beneficiario = strlen(Utils::onlyNumbers($inscricao)) == 14 ? 2 : 1;
                        $detalhe->segmento_j52->numero_inscricao_beneficiario = Utils::onlyNumbers($inscricao);
                        unset($detalhe->segmento_a);
                        unset($detalhe->segmento_b);
                        unset($detalhe->segmento_o);
                        unset($detalhe->segmento_j52);
                        unset($detalhe->segmento_bpix);

                        $lote->inserirDetalhe($detalhe);
                    }
                } else {

                    $lotQuantityDetails++;
                    $lote->header->tipo_servico = '20';

                    //detalhes do seguimento A
                    //$detalhe->segmento_a->codigo_camara_centralizadora = Utils::centralizadoraBB($key);
                    $detalhe->segmento_a->camara_centralizadora = $key == 45 ? '009' : '000';
                    $detalhe->segmento_a->lote_servico = $lotQuantity;
                    $detalhe->segmento_a->numero_registro = $lotQuantityDetails;
                    $detalhe->segmento_a->tipo_movimento = strlen(Utils::onlyNumbers($inscricao)) > 11 ? 003 : 001;
                    $detalhe->segmento_a->codigo_banco_favorecido = $installment->bank_account_provider->bank->bank_code ?? '000';
                    $detalhe->segmento_a->agencia_favorecido = $installment->bank_account_provider->agency_number ?? '0000';
                    $detalhe->segmento_a->verificador_agencia_favorecido = $installment->bank_account_provider->agency_check_number ?? '0';
                    $detalhe->segmento_a->conta_favorecido = $installment->bank_account_provider->account_number ?? '000000';
                    $detalhe->segmento_a->verificador_conta_favorecido = $installment->bank_account_provider->account_check_number ?? '0';
                    $detalhe->segmento_a->nome_favorecido = Utils::formatCnab('X', $nomeBeneficiario, '30');
                    $detalhe->segmento_a->n_docto_atribuido_empresa = $installment->id;
                    $dataPagamento = new Carbon($installment->extension_date); //validar
                    $detalhe->segmento_a->data_pagamento = $dataPagamento->format('dmY');
                    $detalhe->segmento_a->valor_pagamento = Utils::formatCnab('9', number_format($amount, 2), 15);
                    $detalhe->segmento_a->identificacao_transferencia = $key == 45 ? Utils::identificacaoTipoTransferencia($installment->bank_account_provider->account_type ?? 3) : '00';
                    $detalhe->segmento_a->numero_inscricao_favorecido = Utils::onlyNumbers($inscricao);

                    unset($detalhe->segmento_j);
                    unset($detalhe->segmento_j52);
                    unset($detalhe->segmento_o);

                    if ($detalhe->segmento_a->identificacao_transferencia == '04') {
                        $lotQuantityDetails++;
                        $detalhe->segmento_bpix->lote_servico = $lotQuantity;
                        $detalhe->segmento_bpix->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_bpix->tipo_chave = Utils::typeKeyPix($installment->bank_account_provider);
                        $detalhe->segmento_bpix->numero_inscricao_favorecido = Utils::onlyNumbers($inscricao);
                        $detalhe->segmento_bpix->tipo_inscricao = strlen(Utils::onlyNumbers($inscricao)) > 11 ? 2 : 1;
                        $detalhe->segmento_bpix->chave_pix = Utils::formatPixKey($installment->bank_account_provider);
                    } else {
                        unset($detalhe->segmento_bpix);
                    }

                    $lote->inserirDetalhe($detalhe);
                }
            }

            $lotQuantityDetails + 2;
            $lote->trailer->lote_servico = $lote->sequencial;
            $lote->trailer->quantidade_registros_lote = $lotQuantityDetails + 2; // quantidade de Registros do Lote correspondente à soma da quantidade dos registros tipo 1 (header_lote), 3(detalhes) e 5(trailer_lote)
            $lote->trailer->quantidade_cobranca_simples = 1;
            $lote->trailer->quantidade_cobranca_vinculada = 0;
            $lote->trailer->valor_total_cobranca_vinculada = 0;
            $lote->trailer->aviso_bancario = '00000000';
            $lote->trailer->somatoria_lote = Utils::formatCnab('9', number_format($lotValue, 2), 15);

            $sumDetails +=  $lotQuantityDetails; //somar todos registros

            $remessa->inserirLote($lote);
        }


        $remessa->trailer->total_lotes = $lotQuantity;
        $remessa->trailer->total_registros = $sumDetails + ($lotQuantity * 2) + 2;

        return $remessa;
    }

    public static function gerarRemessaSantander($remessa, $company, $bankAccount, $allGroupedInstallment, $installmentsIds, $headerDate, $headerTime)
    {

        $remessa->header->codigo_banco = Utils::formatCnab('9', $bankAccount->bank->bank_code, 3);
        $remessa->header->tipo_inscricao = 2; // CNPJ
        $remessa->header->inscricao_numero = Utils::formatCnab('9', Utils::onlyNumbers($company->cnpj), 14);
        $remessa->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
        $remessa->header->agencia = Utils::formatCnab('9', $bankAccount->agency_number, 5);
        $remessa->header->convenio = Utils::formatCnab('X', $bankAccount->covenant, 20);
        $remessa->header->digito_verificador_agencia = Utils::formatCnab('X', $bankAccount->agency_check_number, 1);
        $remessa->header->conta = Utils::formatCnab('9', $bankAccount->account_number, 12);
        $remessa->header->digito_verificador_conta = Utils::formatCnab('X', $bankAccount->account_check_number, 1);
        $remessa->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
        $remessa->header->data_geracao = $headerDate;
        $remessa->header->hora_geracao = $headerTime;
        //$remessa->header->numero_sequencial_arquivo_retorno = 1;

        $lotQuantity = 0;
        $sumDetails = 0;

        foreach ($allGroupedInstallment as $key => $groupedInstallment) {

            $lotQuantity += 1;

            $lotQuantityDetails = 0;
            $lotValue = 0;

            $lote = $remessa->novoLote($lotQuantity);

            $lote->header->agencia = $bankAccount->agency_number;
            $lote->header->digito_verificador_agencia = $bankAccount->agency_check_number ?? '';
            $lote->header->conta = $bankAccount->account_number;
            $lote->header->digito_verificador_conta = $bankAccount->account_check_number ?? '';
            $lote->header->numero_convenio = Utils::formatCnab('X', $bankAccount->covenant, 20);
            $lote->header->codigo_banco = $bankAccount->bank->bank_code;
            $lote->header->lote_servico = $lote->sequencial;
            $lote->header->tipo_registro = 1;
            $lote->header->tipo_operacao = 'C';
            $lote->header->tipo_servico = '98';
            $lote->header->inscricao_numero = Utils::onlyNumbers($company->cnpj);
            $lote->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
            $lote->header->tipo_inscricao = 2;
            $lote->header->data_gravacao = date('dmY');
            $lote->header->data_credito = date('dmY');
            $lote->header->forma_lancamento  = $key;
            $lote->header->versao_layout = $key == '11' ? '030' : '031';

            foreach ($groupedInstallment as $installment) {

                $dataBankInstallment = Utils::getBankDetailsCnab($installment);
                $nomeBeneficiario = $dataBankInstallment['nomeBeneficiario'];
                $inscricao = $dataBankInstallment['inscricao'];

                $detalhe = $lote->novoDetalhe();
                $amount = ($installment->initial_value + $installment->fees + $installment->fine) - $installment->discount;

                $lotValue += $amount;

                if ($installment->group_form_payment_id == 1 && $installment->bar_code != null) {
                    if ($installment->type_billet == 4) {
                        $lotQuantityDetails++;
                        $detalhe->segmento_o->lote_servico = $lote->sequencial;
                        $detalhe->segmento_o->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_o->codigo_barras = Utils::codigoBarrasBB(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_o->nome_beneficiario = Utils::formatCnab('X',  $nomeBeneficiario, '30');
                        $dataVencimento = new Carbon($installment->extension_date); // data vendimento
                        $detalhe->segmento_o->vencimento = $dataVencimento->format('dmY');
                        $detalhe->segmento_o->valor = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $dataPagamento = new Carbon($installment->extension_date); // validar
                        $detalhe->segmento_o->data_pagamento = $dataPagamento->format('dmY');
                        $detalhe->segmento_o->valor_pagamento = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $detalhe->segmento_o->identificacao_titulo_empresa = $installment->id; //id parcela;
                        unset($detalhe->segmento_a);
                        unset($detalhe->segmento_b);
                        unset($detalhe->segmento_j);
                        unset($detalhe->segmento_j52);
                        $lote->inserirDetalhe($detalhe);
                    } else {
                        $lotQuantityDetails++;
                        $detalhe->segmento_j->lote_servico = $lote->sequencial;
                        $detalhe->segmento_j->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_j->codigo_barras = Utils::codigoBarrasBB(Utils::onlyNumbers($installment->bar_code));
                        $detalhe->segmento_j->nome_beneficiario = Utils::formatCnab('X', $nomeBeneficiario, '30');
                        $dataVencimento = new Carbon($installment->extension_date); // data vendimento
                        $detalhe->segmento_j->vencimento = $dataVencimento->format('dmY');
                        $detalhe->segmento_j->valor = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $dataPagamento = new Carbon($installment->extension_date); // validar
                        $detalhe->segmento_j->data_pagamento = $dataPagamento->format('dmY');
                        $detalhe->segmento_j->valor_pagamento = Utils::formatCnab('9', number_format($amount, 2), 15);
                        $detalhe->segmento_j->identificacao_titulo_empresa = $installment->id; //id parcela;

                        //segmento j52 detalhe
                        $lotQuantityDetails++;
                        $detalhe->segmento_j52->lote_servico = $lotQuantity;
                        $detalhe->segmento_j52->numero_registro = $lotQuantityDetails;
                        $detalhe->segmento_j52->numero_inscricao_pagador = Utils::onlyNumbers($company->cnpj);
                        $detalhe->segmento_j52->tipo_inscricao_beneficiario = strlen(Utils::onlyNumbers($inscricao)) == 14 ? 2 : 1;
                        $detalhe->segmento_j52->numero_inscricao_beneficiario = Utils::onlyNumbers($inscricao);
                        unset($detalhe->segmento_a);
                        unset($detalhe->segmento_b);
                        unset($detalhe->segmento_o);
                        $lote->inserirDetalhe($detalhe);
                    }
                } else {

                    $lotQuantityDetails++;
                    $lote->header->tipo_servico = '20';

                    //detalhes do seguimento A
                    $detalhe->segmento_a->codigo_camara_centralizadora = Utils::centralizadoraBB($key);
                    $detalhe->segmento_a->lote_servico = $lotQuantity;
                    $detalhe->segmento_a->numero_registro = $lotQuantityDetails;
                    $detalhe->segmento_a->codigo_banco_favorecido = $installment->bank_account_provider->bank->bank_code;
                    $detalhe->segmento_a->agencia_favorecido = $installment->bank_account_provider->agency_number;
                    $detalhe->segmento_a->verificador_agencia_favorecido = $installment->bank_account_provider->agency_check_number ?? '';
                    $detalhe->segmento_a->conta_favorecido = $installment->bank_account_provider->account_number;
                    $detalhe->segmento_a->verificador_conta_favorecido = $installment->bank_account_provider->account_check_number ?? '';
                    $detalhe->segmento_a->nome_favorecido = Utils::formatCnab('X', $nomeBeneficiario, '30');
                    $detalhe->segmento_a->n_docto_atribuido_empresa = $installment->id;
                    $dataPagamento = new Carbon($installment->extension_date);
                    $detalhe->segmento_a->data_pagamento = $dataPagamento->format('dmY');
                    $detalhe->segmento_a->valor_pagamento = Utils::formatCnab('9', number_format($amount, 2), 15);

                    //detalhes do seguimento B
                    $lotQuantityDetails++;
                    $detalhe->segmento_b->lote_servico = $lotQuantity;
                    $detalhe->segmento_b->numero_registro = $lotQuantityDetails;
                    $detalhe->segmento_b->tipo_inscricao_favorecido = strlen(Utils::onlyNumbers($inscricao)) == 14 ? 2 : 1;
                    $detalhe->segmento_b->inscricao_favorecido = Utils::onlyNumbers($inscricao);
                    $dataVencimento = new Carbon($installment->extension_date); // data vendimento
                    $detalhe->segmento_b->vencimento = $dataVencimento->format('dmY');
                    unset($detalhe->segmento_j);
                    unset($detalhe->segmento_j52);
                    unset($detalhe->segmento_o);
                    $lote->inserirDetalhe($detalhe);
                }
            }

            $lotQuantityDetails + 2;
            $lote->trailer->lote_servico = $lote->sequencial;
            $lote->trailer->quantidade_registros_lote = $lotQuantityDetails + 2; // quantidade de Registros do Lote correspondente à soma da quantidade dos registros tipo 1 (header_lote), 3(detalhes) e 5(trailer_lote)
            $lote->trailer->quantidade_cobranca_simples = 1;
            $lote->trailer->quantidade_cobranca_vinculada = 0;
            $lote->trailer->valor_total_cobranca_vinculada = 0;
            $lote->trailer->aviso_bancario = '00000000';
            $lote->trailer->somatoria_lote = Utils::formatCnab('9', number_format($lotValue, 2), 15);

            $sumDetails +=  $lotQuantityDetails; //somar todos registros

            $remessa->inserirLote($lote);
        }


        $remessa->trailer->total_lotes = $lotQuantity;
        $remessa->trailer->total_registros = $sumDetails + ($lotQuantity * 2) + 2;

        return $remessa;
    }
}

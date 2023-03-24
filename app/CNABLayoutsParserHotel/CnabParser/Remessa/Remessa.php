<?php

namespace App\CNABLayoutsParserHotel\CnabParser\Remessa;

use App\Services\Utils;
use Carbon\Carbon;

class Remessa
{
    // public static function gerarRemessaItau($remessa, $company, $bankAccount, $allGroupedBilling)
    // {
    //     $remessa->header->codigo_banco = $bankAccount->bank->bank_code;
    //     $remessa->header->tipo_inscricao = 2; // CNPJ
    //     $remessa->header->inscricao_numero = Utils::onlyNumbers($company->cnpj);
    //     $remessa->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
    //     $remessa->header->agencia = $bankAccount->agency_number;
    //     $remessa->header->digito_verificador_agencia = $bankAccount->agency_check_number;
    //     $remessa->header->conta = $bankAccount->account_number;
    //     $remessa->header->digito_verificador_conta = $bankAccount->account_check_number;
    //     $remessa->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
    //     $remessa->header->data_geracao = date('dmY');
    //     $remessa->header->hora_geracao = date('His');
    //     $remessa->header->numero_sequencial_arquivo_retorno = 1;

    //     $lotQuantity = 0;
    //     $sumDetails = 0;

    //     foreach ($allGroupedBilling as $key => $groupedBilling) {

    //         $lotQuantity += 1;

    //         $lotQuantityDetails = 0;
    //         $lotValue = 0;

    //         $lote = $remessa->novoLote($lotQuantity);

    //         $lote->header->agencia = $bankAccount->agency_number;
    //         $lote->header->digito_verificador_agencia = $bankAccount->agency_check_number;
    //         $lote->header->conta = $bankAccount->account_number;
    //         $lote->header->digito_verificador_conta = $bankAccount->account_check_number;
    //         $lote->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
    //         $lote->header->codigo_banco = $bankAccount->bank->bank_code;
    //         $lote->header->lote_servico = $lote->sequencial;
    //         $lote->header->tipo_registro = 1;
    //         $lote->header->tipo_operacao = 'C';
    //         $lote->header->tipo_servico = '98';
    //         $lote->header->inscricao_numero = Utils::onlyNumbers($company->cnpj);
    //         $lote->header->numero_convenio = Utils::formatCnab('9', $bankAccount->covenant, 9);
    //         $lote->header->nome_empresa = Utils::formatCnab('X', $company->company_name, 30);
    //         $lote->header->tipo_inscricao = 2;
    //         $lote->header->data_gravacao = date('dmY');
    //         $lote->header->data_credito = date('dmY');
    //         $lote->header->forma_pagamento  = $key;
    //         $lote->header->endereco = Utils::formatCnab('X', $company->address, 30);
    //         $lote->header->numero = Utils::formatCnab('9', Utils::onlyNumbers($company->number) == '' ? 0 : Utils::onlyNumbers($company->number), 5);
    //         $lote->header->complemento = Utils::formatCnab('X', $company->complement, 15);
    //         $lote->header->cidade = Utils::formatCnab('X', $company->city->title, 15);
    //         $lote->header->cep = Utils::onlyNumbers($company->cep);
    //         $lote->header->uf = Utils::formatCnab('X', $company->city->state->uf, 2);

    //         foreach ($groupedBilling as $billing) {

    //             $detalhe = $lote->novoDetalhe();

    //             $lotValue += $billing->supplier_value;

    //             if ($billing->form_of_payment == 0 && $billing->boleto_code != null) {
    //                 $lotQuantityDetails++;
    //                 $detalhe->segmento_j->lote_servico = $lote->sequencial;
    //                 $detalhe->segmento_j->numero_registro = $lotQuantityDetails;
    //                 $detalhe->segmento_j->nome_beneficiario = Utils::formatCnab('X', $billing->recipient_name, '30');
    //                 $dataVencimento = new Carbon($billing->pay_date); // data vendimento
    //                 $detalhe->segmento_j->vencimento = $dataVencimento->format('dmY');
    //                 $detalhe->segmento_j->valor = Utils::formatCnab('9', number_format($billing->boleto_value, 2), 15);
    //                 $dataPagamento = new Carbon($billing->pay_date); //VALIDAR
    //                 $detalhe->segmento_j->data_pagamento = $dataPagamento->format('dmY');
    //                 $detalhe->segmento_j->valor_pagamento = Utils::formatCnab('9', number_format($billing->boleto_value, 2), 15);
    //                 $detalhe->segmento_j->identificacao_titulo_empresa = $billing->id; //id parcela;
    //                 $detalhe->segmento_j->boleto_banco_favorecido = Utils::codigoBancoFavorecidoBoleto(Utils::onlyNumbers($billing->boleto_code));
    //                 $detalhe->segmento_j->boleto_dv_favorecido = Utils::dvBoleto(Utils::onlyNumbers($billing->boleto_code));
    //                 $detalhe->segmento_j->boleto_vencimento_favorecido = Utils::fatorVencimentoBoleto(Utils::onlyNumbers($billing->boleto_code));
    //                 $detalhe->segmento_j->boleto_moeda_favorecido = Utils::codigoMoedaBoleto(Utils::onlyNumbers($billing->boleto_code));
    //                 $detalhe->segmento_j->boleto_valor_favorecido = Utils::valorBoleto(Utils::onlyNumbers($billing->boleto_code));
    //                 $detalhe->segmento_j->boleto_campo_livre_favorecido = Utils::campoLivreBoleto(Utils::onlyNumbers($billing->boleto_code));


    //                 //segmento j52 detalhe
    //                 $lotQuantityDetails++;
    //                 $detalhe->segmento_j52->lote_servico = $lotQuantity;
    //                 $detalhe->segmento_j52->numero_registro = $lotQuantityDetails;
    //                 $detalhe->segmento_j52->numero_inscricao_pagador = Utils::onlyNumbers($company->cnpj);
    //                 $detalhe->segmento_j52->tipo_inscricao_beneficiario = strlen(Utils::onlyNumbers($billing->cnpj)) == 14 ? 2 : 1;
    //                 $detalhe->segmento_j52->numero_inscricao_beneficiario = Utils::onlyNumbers($billing->cnpj);
    //                 unset($detalhe->segmento_a);
    //                 unset($detalhe->segmento_b);

    //                 $lote->inserirDetalhe($detalhe);
    //             } else {

    //                 $lotQuantityDetails++;
    //                 $lote->header->tipo_servico = '20';

    //                 //detalhes do seguimento A
    //                 $detalhe->segmento_a->codigo_camara_centralizadora = Utils::centralizadoraBB($key);
    //                 $detalhe->segmento_a->lote_servico = $lotQuantity;
    //                 $detalhe->segmento_a->numero_registro = $lotQuantityDetails;
    //                 //$detalhe->segmento_a->tipo_movimento = strlen(Utils::onlyNumbers($paymentRequest->provider->provider_type)) == 'J' ? 002 : 001;
    //                 $detalhe->segmento_a->codigo_banco_favorecido = $billing->bank_account->bank->bank_code;
    //                 $detalhe->segmento_a->agencia_favorecido = $billing->bank_account->agency_number;
    //                 $detalhe->segmento_a->verificador_agencia_favorecido = $billing->bank_account->agency_check_number;
    //                 $detalhe->segmento_a->conta_favorecido = $billing->bank_account->account_number;
    //                 $detalhe->segmento_a->verificador_conta_favorecido = $billing->bank_account->account_check_number;
    //                 $detalhe->segmento_a->nome_favorecido = Utils::formatCnab('X', $billing->recipient_name, '30');
    //                 $detalhe->segmento_a->n_docto_atribuido_empresa = $billing->id;
    //                 $dataPagamento = new Carbon($billing->pay_date); //validar
    //                 $detalhe->segmento_a->data_pagamento = $dataPagamento->format('dmY');
    //                 $detalhe->segmento_a->valor_pagamento = Utils::formatCnab('9', number_format($billing->supplier_value, 2), 15);
    //                 $detalhe->segmento_a->identificacao_transferencia = Utils::identificacaoTipoTransferencia($billing->bank_account->account_type ?? 3);
    //                 $detalhe->segmento_a->numero_inscricao_favorecido = Utils::onlyNumbers($billing->cnpj);

    //                 unset($detalhe->segmento_j);
    //                 unset($detalhe->segmento_j52);
    //                 $lote->inserirDetalhe($detalhe);
    //             }
    //         }

    //         $lotQuantityDetails + 2;
    //         $lote->trailer->lote_servico = $lote->sequencial;
    //         $lote->trailer->quantidade_registros_lote = $lotQuantityDetails + 2; // quantidade de Registros do Lote correspondente à soma da quantidade dos registros tipo 1 (header_lote), 3(detalhes) e 5(trailer_lote)
    //         $lote->trailer->quantidade_cobranca_simples = 1;
    //         $lote->trailer->quantidade_cobranca_vinculada = 0;
    //         $lote->trailer->valor_total_cobranca_vinculada = 0;
    //         $lote->trailer->aviso_bancario = '00000000';
    //         $lote->trailer->somatoria_lote = Utils::formatCnab('9', number_format($lotValue, 2), 15);

    //         $sumDetails +=  $lotQuantityDetails; //somar todos registros

    //         $remessa->inserirLote($lote);
    //     }


    //     $remessa->trailer->total_lotes = $lotQuantity;
    //     $remessa->trailer->total_registros = $sumDetails + ($lotQuantity * 2) + 2;

    //     return $remessa;
    // }

    public static function gerarRemessaItau($remessa, $company, $bankAccount, $allGroupedBilling)
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
        $remessa->header->data_geracao = date('dmY');
        $remessa->header->hora_geracao = date('His');
        $remessa->header->numero_sequencial_arquivo_retorno = 1;

        $lotQuantity = 0;
        $sumDetails = 0;

        foreach ($allGroupedBilling as $key => $groupedBilling) {

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

            foreach ($groupedBilling as $billing) {

                $detalhe = $lote->novoDetalhe();

                $lotValue += $billing->boleto_value;

                if ($billing->form_of_payment == 0 && $billing->boleto_code != null) {
                    $lotQuantityDetails++;
                    $detalhe->segmento_j->lote_servico = $lote->sequencial;
                    $detalhe->segmento_j->numero_registro = $lotQuantityDetails;
                    $detalhe->segmento_j->nome_beneficiario = Utils::formatCnab('X', $billing->recipient_name, '30');
                    $dataVencimento = new Carbon($billing->pay_date); // data vendimento
                    $detalhe->segmento_j->vencimento = $dataVencimento->format('dmY');
                    $detalhe->segmento_j->valor = Utils::formatCnab('9', number_format($billing->boleto_value, 2), 15);
                    $dataPagamento = new Carbon($billing->pay_date); //VALIDAR
                    $detalhe->segmento_j->data_pagamento = $dataPagamento->format('dmY');
                    $detalhe->segmento_j->valor_pagamento = Utils::formatCnab('9', number_format($billing->boleto_value, 2), 15);
                    $detalhe->segmento_j->identificacao_titulo_empresa = $billing->id; //id parcela;
                    $detalhe->segmento_j->boleto_banco_favorecido = Utils::codigoBancoFavorecidoBoleto(Utils::onlyNumbers($billing->boleto_code));
                    $detalhe->segmento_j->boleto_dv_favorecido = Utils::dvBoleto(Utils::onlyNumbers($billing->boleto_code));
                    $detalhe->segmento_j->boleto_vencimento_favorecido = Utils::fatorVencimentoBoleto(Utils::onlyNumbers($billing->boleto_code));
                    $detalhe->segmento_j->boleto_moeda_favorecido = Utils::codigoMoedaBoleto(Utils::onlyNumbers($billing->boleto_code));
                    $detalhe->segmento_j->boleto_valor_favorecido = Utils::valorBoleto(Utils::onlyNumbers($billing->boleto_code));
                    $detalhe->segmento_j->boleto_campo_livre_favorecido = Utils::campoLivreBoleto(Utils::onlyNumbers($billing->boleto_code));


                    //segmento j52 detalhe
                    $lotQuantityDetails++;
                    $detalhe->segmento_j52->lote_servico = $lotQuantity;
                    $detalhe->segmento_j52->numero_registro = $lotQuantityDetails;
                    $detalhe->segmento_j52->numero_inscricao_pagador = Utils::onlyNumbers($company->cnpj);
                    $detalhe->segmento_j52->tipo_inscricao_beneficiario = strlen(Utils::onlyNumbers($billing->cnpj)) == 14 ? 2 : 1;
                    $detalhe->segmento_j52->numero_inscricao_beneficiario = Utils::onlyNumbers($billing->cnpj);
                    unset($detalhe->segmento_a);
                    unset($detalhe->segmento_b);

                    $lote->inserirDetalhe($detalhe);
                } else {

                    $lotQuantityDetails++;
                    $lote->header->tipo_servico = '20';

                    //detalhes do seguimento A
                    $detalhe->segmento_a->codigo_camara_centralizadora = Utils::centralizadoraBB($key);
                    $detalhe->segmento_a->lote_servico = $lotQuantity;
                    $detalhe->segmento_a->numero_registro = $lotQuantityDetails;
                    //$detalhe->segmento_a->tipo_movimento = strlen(Utils::onlyNumbers($paymentRequest->provider->provider_type)) == 'J' ? 002 : 001;
                    $detalhe->segmento_a->codigo_banco_favorecido = $billing->billings[0]->bank_account->bank->bank_code;
                    $detalhe->segmento_a->agencia_favorecido = $billing->billings[0]->bank_account->agency_number;
                    $detalhe->segmento_a->verificador_agencia_favorecido = $billing->billings[0]->bank_account->agency_check_number;
                    $detalhe->segmento_a->conta_favorecido = $billing->billings[0]->bank_account->account_number;
                    $detalhe->segmento_a->verificador_conta_favorecido = $billing->billings[0]->bank_account->account_check_number;
                    $detalhe->segmento_a->nome_favorecido = Utils::formatCnab('X', $billing->billings[0]->recipient_name, '30');
                    $detalhe->segmento_a->n_docto_atribuido_empresa = $billing->billings[0]->id;
                    $dataPagamento = new Carbon($billing->billings[0]->pay_date); //validar
                    $detalhe->segmento_a->data_pagamento = $dataPagamento->format('dmY');
                    $detalhe->segmento_a->valor_pagamento = Utils::formatCnab('9', number_format($billing->billings[0]->supplier_value, 2), 15);
                    $detalhe->segmento_a->identificacao_transferencia = Utils::identificacaoTipoTransferencia($billing->billings[0]->bank_account->account_type ?? 3);
                    $detalhe->segmento_a->numero_inscricao_favorecido = Utils::onlyNumbers($billing->billings[0]->cnpj);

                    unset($detalhe->segmento_j);
                    unset($detalhe->segmento_j52);
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

<?php

namespace App\CNABLayoutsParserHotel\CnabParser\Retorno;

class Itau
{
    public static function codeReturnItau($code)
    {
        switch ($code) {
            case '00':
                return 'Este código indica que o pagamento foi confirmado';
                break;
            case 'AE':
                return 'DATA DE PAGAMENTO ALTERADA';
                break;
            case 'AG':
                return 'NÚMERO DO LOTE INVÁLIDO';
                break;
            case 'AH':
                return ' NÚMERO SEQUENCIAL DO REGISTRO NO LOTE INVÁLIDO';
                break;
            case 'AI':
                return 'PRODUTO DEMONSTRATIVO DE PAGAMENTO NÃO CONTRATADO';
                break;
            case 'AJ':
                return 'TIPO DE MOVIMENTO INVÁLIDO';
                break;
            case 'AL':
                return 'CÓDIGO DO BANCO FAVORECIDO INVÁLIDO';
                break;
            case 'AM':
                return 'AGÊNCIA DO FAVORECIDO INVÁLIDA';
                break;
            case 'AN':
                return 'CONTA CORRENTE DO FAVORECIDO INVÁLIDA';
                break;
            case 'AO':
                return 'NOME DO FAVORECIDO INVÁLIDO';
                break;
            case 'AP':
                return 'DATA DE PAGAMENTO / DATA DE VALIDADE / HORA DE LANÇAMENTO / ARRECADAÇÃO / APURAÇÃO INVÁLIDA';
                break;
            case 'AQ':
                return 'QUANTIDADE DE REGISTROS MAIOR QUE 999999';
                break;
            case 'AR':
                return 'VALOR ARRECADADO / LANÇAMENTO INVÁLIDO';
                break;
            case 'BC':
                return 'NOSSO NÚMERO INVÁLIDO';
                break;
            case 'BD':
                return 'PAGAMENTO AGENDADO';
                break;
            case 'BE':
                return 'PAGAMENTO AGENDADO COM FORMA ALTERADA PARA OP';
                break;
            case 'BI':
                return 'CNPJ / CPF DO FAVORECIDO NO SEGMENTO J-52 ou B INVÁLIDO / DOCUMENTO FAVORECIDO INVÁLIDO PIX';
                break;
            case 'BL':
                return 'VALOR DA PARCELA INVÁLIDO';
                break;
            case 'CD':
                return 'CNPJ / CPF INFORMADO DIVERGENTE DO CADASTRADO';
                break;
            case 'CE':
                return 'PAGAMENTO CANCELADO';
                break;
            case 'CF':
                return 'VALOR DO DOCUMENTO INVÁLIDO / VALOR DIVERGENTE DO QR CODE';
                break;
            case 'CG':
                return 'VALOR DO ABATIMENTO INVÁLIDO';
                break;
            case 'CH':
                return 'VALOR DO DESCONTO INVÁLIDO';
                break;
            case 'CI':
                return 'CNPJ / CPF / IDENTIFICADOR / INSCRIÇÃO ESTADUAL / INSCRIÇÃO NO CAD / ICMS INVÁLIDO';
                break;
            case 'CJ':
                return 'VALOR DA MULTA INVÁLIDO';
                break;
            case 'CK':
                return 'TIPO DE INSCRIÇÃO INVÁLIDA';
                break;
            case 'CL':
                return 'VALOR DO INSS INVÁLIDO';
                break;
            case 'CM':
                return 'VALOR DO COFINS INVÁLIDO';
                break;
            case 'CN':
                return 'CONTA NÃO CADASTRADA';
                break;
            case 'CO':
                return 'VALOR DE OUTRAS ENTIDADES INVÁLIDO';
                break;
            case 'CP':
                return 'CONFIRMAÇÃO DE OP CUMPRIDA';
                break;
            case 'CQ':
                return 'SOMA DAS FATURAS DIFERE DO PAGAMENTO';
                break;
            case 'CR':
                return 'VALOR DO CSLL INVÁLIDO';
                break;
            case 'CS':
                return 'DATA DE VENCIMENTO DA FATURA INVÁLIDA';
                break;
            case 'DA':
                return 'NÚMERO DE DEPEND. SALÁRIO FAMILIA INVALIDO';
                break;
            case 'DB':
                return 'NÚMERO DE HORAS SEMANAIS INVÁLIDO';
                break;
            case 'DC':
                return 'SALÁRIO DE CONTRIBUIÇÃO INSS INVÁLIDO';
                break;
            case 'DD':
                return 'SALÁRIO DE CONTRIBUIÇÃO FGTS INVÁLIDO';
                break;
            case 'DE':
                return 'VALOR TOTAL DOS PROVENTOS INVÁLIDO';
                break;
            case 'DF':
                return 'VALOR TOTAL DOS DESCONTOS INVÁLIDO';
                break;
            case 'DG':
                return 'VALOR LÍQUIDO NÃO NUMÉRICO';
                break;
            case 'DH':
                return 'VALOR LIQ. INFORMADO DIFERE DO CALCULADO';
                break;
            case 'DI':
                return 'VALOR DO SALÁRIO-BASE INVÁLIDO';
                break;
            case 'DJ':
                return 'BASE DE CÁLCULO IRRF INVÁLIDA';
                break;
            case 'DK':
                return 'BASE DE CÁLCULO FGTS INVÁLIDA';
                break;
            case 'DL':
                return 'FORMA DE PAGAMENTO INCOMPATÍVEL COM HOLERITE';
                break;
            case 'DM':
                return 'E-MAIL DO FAVORECIDO INVÁLIDO';
                break;
            case 'DV':
                return 'DOC / TED DEVOLVIDO PELO BANCO FAVORECIDO';
                break;
            case 'D0':
                return 'FINALIDADE DO HOLERITE INVÁLIDA';
                break;
            case 'D1':
                return 'MÊS DE COMPETENCIA DO HOLERITE INVÁLIDA';
                break;
            case 'D2':
                return 'DIA DA COMPETENCIA DO HOLETITE INVÁLIDA';
                break;
            case 'D3':
                return 'CENTRO DE CUSTO INVÁLIDO';
                break;
            case 'D4':
                return 'CAMPO NUMÉRICO DA FUNCIONAL INVÁLIDO';
                break;
            case 'D5':
                return 'DATA INÍCIO DE FÉRIAS NÃO NUMÉRICA';
                break;
            case 'D6':
                return 'DATA INÍCIO DE FÉRIAS INCONSISTENTE';
                break;
            case 'D7':
                return 'DATA FIM DE FÉRIAS NÃO NUMÉRICO';
                break;
            case 'D8':
                return 'DATA FIM DE FÉRIAS INCONSISTENTE';
                break;
            case 'D9':
                return 'NÚMERO DE DEPENDENTES IR INVÁLIDO';
                break;
            case 'EM':
                return 'CONFIRMAÇÃO DE OP EMITIDA';
                break;
            case 'EX':
                return 'DEVOLUÇÃO DE OP NÃO SACADA PELO FAVORECIDO';
                break;
            case 'E0':
                return 'TIPO DE MOVIMENTO HOLERITE INVÁLIDO';
                break;
            case 'E1':
                return 'VALOR 01 DO HOLERITE / INFORME INVÁLIDO';
                break;
            case 'E2':
                return 'VALOR 02 DO HOLERITE / INFORME INVÁLIDO';
                break;
            case 'E3':
                return 'VALOR 03 DO HOLERITE / INFORME INVÁLIDO';
                break;
            case 'E4':
                return 'VALOR 04 DO HOLERITE / INFORME INVÁLIDO';
                break;
            case 'FC':
                return 'PAGAMENTO EFETUADO ATRAVÉS DE FINANCIAMENTO COMPROR';
                break;
            case 'FD':
                return ' PAGAMENTO EFETUADO ATRAVÉS DE FINANCIAMENTO DESCOMPROR';
                break;
            case 'HÁ':
                return 'ERRO NO LOTE';
                break;
            case 'HM':
                return 'ERRO NO REGISTRO HEADER DE ARQUIVO';
                break;
            case 'IB':
                return 'VALOR DO DOCUMENTO INVÁLIDO';
                break;
            case 'IC':
                return 'VALOR DO ABATIMENTO INVÁLIDO';
                break;
            case 'ID':
                return 'VALOR DO DESCONTO INVÁLIDO';
                break;
            case 'IE':
                return 'VALOR DA MORA INVÁLIDO';
                break;
            case 'IF':
                return 'VALOR DA MULTA INVÁLIDO';
                break;
            case 'IG':
                return 'VALOR DA DEDUÇÃO INVÁLIDO';
                break;
            case 'IH':
                return 'VALOR DO ACRÉSCIMO INVÁLIDO';
                break;
            case 'II':
                return 'DATA DE VENCIMENTO INVÁLIDA / QR CODE EXPIRADO';
                break;
            case 'IJ':
                return 'COMPETÊNCIA / PERÍODO REFERÊNCIA / PARCELA INVÁLIDA';
                break;
            case 'IK':
                return 'TRIBUTO NÃO LIQUIDÁVEL VIA SISPAG OU NÃO CONVENIADO COM ITAÚ';
                break;
            case 'IL':
                return 'CÓDIGO DE PAGAMENTO / EMPRESA /RECEITA INVÁLIDO';
                break;
            case 'IM':
                return 'TIPO X FORMA NÃO COMPATÍVEL';
                break;
            case 'IN':
                return 'BANCO/AGÊNCIA NÃO CADASTRADOS';
                break;
            case 'IO':
                return 'DAC / VALOR / COMPETÊNCIA / IDENTIFICADOR DO LACRE INVÁLIDO / IDENTIFICAÇÃO DO QR CODE INVÁLIDO';
                break;
            case 'IP':
                return 'DAC DO CÓDIGO DE BARRAS INVÁLIDO / ERRO NA VALIDAÇÃO DO QR CODE';
                break;
            case 'IQ':
                return 'DÍVIDA ATIVA OU NÚMERO DE ETIQUETA INVÁLIDO';
                break;
            case 'IR':
                return 'PAGAMENTO ALTERADO';
                break;
            case 'IS':
                return 'CONCESSIONÁRIA NÃO CONVENIADA COM ITAÚ';
                break;
            case 'IT':
                return 'VALOR DO TRIBUTO INVÁLIDO';
                break;
            case 'IU':
                return 'VALOR DA RECEITA BRUTA ACUMULADA INVÁLIDO';
                break;
            case 'IV':
                return 'NÚMERO DO DOCUMENTO ORIGEM / REFERÊNCIA INVÁLIDO';
                break;
            case 'IX':
                return 'CÓDIGO DO PRODUTO INVÁLIDO';
                break;
            case 'LA':
                return 'DATA DE PAGAMENTO DE UM LOTE ALTERADA';
                break;
            case 'LC':
                return 'LOTE DE PAGAMENTOS CANCELADO';
                break;
            case 'NA':
                return 'PAGAMENTO CANCELADO POR FALTA DE AUTORIZAÇÃO';
                break;
            case 'NB':
                return 'IDENTIFICAÇÃO DO TRIBUTO INVÁLIDA';
                break;
            case 'NC':
                return 'EXERCÍCIO (ANO BASE) INVÁLIDO';
                break;
            case 'ND':
                return 'CÓDIGO RENAVAM NÃO ENCONTRADO/INVÁLIDO';
                break;
            case 'NE':
                return 'UF INVÁLIDA';
                break;
            case 'NF':
                return 'CÓDIGO DO MUNICÍPIO INVÁLIDO';
                break;
            case 'NG':
                return 'PLACA INVÁLIDA';
                break;
            case 'NH':
                return 'OPÇÃO/PARCELA DE PAGAMENTO INVÁLIDA';
                break;
            case 'NI':
                return 'TRIBUTO JÁ FOI PAGO OU ESTÁ VENCIDO';
                break;
            case 'NR':
                return 'OPERAÇÃO NÃO REALIZADA';
                break;
            case 'PD':
                return 'AQUISIÇÃO CONFIRMADA (EQUIVALE A OCORRÊNCIA 02 NO LAYOUT DE RISCO SACADO)';
                break;
            case 'RJ':
                return 'REGISTRO REJEITADO';
                break;
            case 'RS':
                return 'PAGAMENTO DISPONÍVEL PARA ANTECIPAÇÃO NO RISCO SACADO – MODALIDADE RISCO SACADO PÓS AUTORIZADO';
                break;
            case 'SS':
                return 'PAGAMENTO CANCELADO POR INSUFICIÊNCIA DE SALDO / LIMITE DIÁRIO DE PAGTO EXCEDIDO';
                break;
            case 'TA':
                return 'LOTE NÃO ACEITO - TOTAIS DO LOTE COM DIFERENÇA';
                break;
            case 'TI':
                return 'TITULARIDADE INVÁLIDA';
                break;
            case 'X1':
                return 'FORMA INCOMPATÍVEL COM LAYOUT 010';
                break;
            case 'X2':
                return 'NÚMERO DA NOTA FISCAL INVÁLIDO';
                break;
            case 'X3':
                return 'IDENTIFICADOR DE NF/CNPJ INVÁLIDO';
                break;
            case 'X4':
                return 'FORMA 32 INVÁLIDA';
                break;
            default:
                return 'Erro desconhecido';
        }
    }

    public static function paymentDone($code)
    {
        switch ($code) {
            case '00':
                return true;
                break;
            default:
                return false;
        }
    }
}

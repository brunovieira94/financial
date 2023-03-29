<?php

namespace App\CNABLayoutsParser\CnabParser\Retorno;

class BancoBrasil
{
    public static function codeReturnBrazilBank($code)
    {
        switch ($code) {
            case '00':
                return 'Este código indica que o pagamento foi confirmado';
                break;
            case '01':
                return 'Insuficiência de Fundos - Débito Não Efetuado';
                break;
            case '02':
                return 'Crédito ou Débito Cancelado pelo Pagador/Credor';
                break;
            case '03':
                return 'Débito Autorizado pela Agência - Efetuado';
                break;
            case 'AA':
                return 'Controle Inválido';
                break;
            case 'AB':
                return 'Tipo de Operação Inválido';
                break;
            case 'AC':
                return 'Tipo de Serviço Inválido';
                break;
            case 'AD':
                return 'Forma de Lançamento Inválida';
                break;
            case 'AE':
                return 'Tipo/Número de Inscrição Inválido';
                break;
            case 'AF':
                return 'Código de Convênio Inválido';
                break;
            case 'AG':
                return 'Agência/Conta Corrente/DV Inválido';
                break;
            case 'AH':
                return 'Nº Seqüencial do Registro no Lote Inválido';
                break;
            case 'AI':
                return 'Código de Segmento de Detalhe Inválido';
                break;
            case 'AJ':
                return 'Tipo de Movimento Inválido';
                break;
            case 'AK':
                return 'Código da Câmara de Compensação do Banco Favorecido/Depositário Inválido';
                break;
            case 'AL':
                return 'Código do Banco Favorecido ou Depositário Inválido';
                break;
            case 'AM':
                return 'Agência Mantenedora da Conta Corrente do Favorecido Inválida';
                break;
            case 'AN':
                return 'Conta Corrente/DV do Favorecido Inválido';
                break;
            case 'AO':
                return 'Nome do Favorecido Não Informado';
                break;
            case 'AP':
                return 'Data Lançamento Inválido';
                break;
            case 'AQ':
                return 'Tipo/Quantidade da Moeda Inválido';
                break;
            case 'AR':
                return 'Valor do Lançamento Inválido';
                break;
            case 'AS':
                return 'Aviso ao Favorecido - Identificação Inválida';
                break;
            case 'AT':
                return 'Tipo/Número de Inscrição do Favorecido Inválido';
                break;
            case 'AU':
                return 'Logradouro do Favorecido Não Informado';
                break;
            case 'AV':
                return 'Nº do Local do Favorecido Não Informado';
                break;
            case 'AW':
                return 'Cidade do Favorecido Não Informada';
                break;
            case 'AX':
                return 'CEP/Complemento do Favorecido Inválido';
                break;
            case 'AY':
                return 'Sigla do Estado do Favorecido Inválida';
                break;
            case 'AZ':
                return 'Código/Nome do Banco Depositário Inválido';
                break;
            case 'BA':
                return ' Código/Nome da Agência Depositária Não Informado';
                break;
            case 'BB':
                return 'Seu Número Inválido';
                break;
            case 'BC':
                return 'Nosso Número Inválido';
                break;
            case 'BD':
                return 'Inclusão Efetuada com Sucesso';
                break;
            case 'BE':
                return 'Alteração Efetuada com Sucesso';
                break;
            case 'BF':
                return 'Exclusão Efetuada com Sucesso';
                break;
            case 'BG':
                return 'Agência/Conta Impedida Legalmente';
                break;
            case 'BH':
                return ' Empresa não pagou salário';
                break;
            case 'BI':
                return 'Falecimento do mutuário';
                break;
            case 'BJ':
                return 'Empresa não enviou remessa do mutuário';
                break;
            case 'BK':
                return 'Empresa não enviou remessa no vencimento';
                break;
            case 'BL':
                return 'Valor da parcela inválida';
                break;
            case 'BM':
                return 'Identificação do contrato inválida';
                break;
            case 'BN':
                return 'Operação de Consignação Incluída com Sucesso';
                break;
            case 'BO':
                return 'Operação de Consignação Alterada com Sucesso';
                break;
            case 'BP':
                return 'Operação de Consignação Excluída com Sucesso';
                break;
            case 'BQ':
                return 'Operação de Consignação Liquidada com Sucesso';
                break;
            case 'BR':
                return 'Reativação Efetuada com Sucesso';
                break;
            case 'BS':
                return 'Suspensão Efetuada com Sucesso';
                break;
            case 'CA':
                return 'Código de Barras - Código do Banco Inválido';
                break;
            case 'CB':
                return 'Código de Barras - Código da Moeda Inválido';
                break;
            case 'CC':
                return 'Código de Barras - Dígito Verificador Geral Inválido';
                break;
            case 'CD':
                return 'Código de Barras - Valor do Título Inválido';
                break;
            case 'CE':
                return 'Código de Barras - Campo Livre Inválido';
                break;
            case 'CF':
                return 'Valor do Documento Inválido';
                break;
            case 'CG':
                return 'Valor do Abatimento Inválido';
                break;
            case 'CH':
                return 'Valor do Desconto Inválido';
                break;
            case 'CI':
                return 'Valor de Mora Inválido';
                break;
            case 'CJ':
                return 'Valor da Multa Inválido';
                break;
            case 'CK':
                return 'Valor do IR Inválido';
                break;
            case 'CL':
                return 'Valor do ISS Inválido';
                break;
            case 'CM':
                return 'Valor do IOF Inválido';
                break;
            case 'CN':
                return 'Valor de Outras Deduções Inválido';
                break;
            case 'CO':
                return 'Valor de Outros Acréscimos Inválido';
                break;
            case 'CP':
                return 'Valor do INSS Inválido';
                break;
            case 'HA':
                return 'Lote Não Aceito';
                break;
            case 'HB':
                return 'Inscrição da Empresa Inválida para o Contrato';
                break;
            case 'HC':
                return 'Convênio com a Empresa Inexistente/Inválido para o Contrato';
                break;
            case 'HD':
                return 'Agência/Conta Corrente da Empresa Inexistente/Inválido para o Contrato';
                break;
            case 'HE':
                return 'Tipo de Serviço Inválido para o Contrato';
                break;
            case 'HF':
                return 'Conta Corrente da Empresa com Saldo Insuficiente';
                break;
            case 'HG':
                return 'Lote de Serviço Fora de Seqüência';
                break;
            case 'HH':
                return 'Lote de Serviço Inválido';
                break;
            case 'HI':
                return 'Arquivo não aceito';
                break;
            case 'HJ':
                return 'Tipo de Registro Inválido';
                break;
            case 'HK':
                return 'Código Remessa / Retorno Inválido';
                break;
            case 'HL':
                return 'Versão de layout inválida';
                break;
            case 'HM':
                return 'Mutuário não identificado';
                break;
            case 'HN':
                return 'Tipo do beneficio não permite empréstimo';
                break;
            case 'HO':
                return 'Beneficio cessado/suspenso';
                break;
            case 'HP':
                return 'Beneficio possui representante legal';
                break;
            case 'HQ':
                return 'Beneficio é do tipo PA (Pensão alimentícia)';
                break;
            case 'HR':
                return 'Quantidade de contratos permitida excedida';
                break;
            case 'HS':
                return 'Beneficio não pertence ao Banco informado';
                break;
            case 'HT':
                return 'Início do desconto informado já ultrapassado';
                break;
            case 'HU':
                return 'Número da parcela inválida';
                break;
            case 'HV':
                return 'Quantidade de parcela inválida';
                break;
            case 'HW':
                return 'Margem consignável excedida para o mutuário dentro do prazo do contrato';
                break;
            case 'HX':
                return 'Empréstimo já cadastrado';
                break;
            case 'HY':
                return 'Empréstimo inexistente';
                break;
            case 'HZ':
                return 'Empréstimo já encerrado';
                break;
            case 'H1':
                return 'Arquivo sem trailer';
                break;
            case 'H2':
                return 'Mutuário sem crédito na competência';
                break;
            case 'H3':
                return 'Não descontado – outros motivos';
                break;
            case 'H4':
                return 'Retorno de Crédito não pago';
                break;
            case 'H5':
                return 'Cancelamento de empréstimo retroativo';
                break;
            case 'H6':
                return 'Outros Motivos de Glos';
                break;
            case 'H7':
                return 'Margem consignável excedida para o mutuário acima do prazo do contrato';
                break;
            case 'H8':
                return 'Mutuário desligado do empregador';
                break;
            case 'H9':
                return 'Mutuário afastado por licença';
                break;
            case 'IA':
                return 'Primeiro nome do mutuário diferente do primeiro nome do movimento do censo ou diferente da base de Titular do Benefício';
                break;
            case 'IB':
                return 'Benefício suspenso/cessado pela APS ou Sisobi';
                break;
            case 'IC':
                return 'Benefício suspenso por dependência de cálculo';
                break;
            case 'ID':
                return 'Benefício suspenso/cessado pela inspetoria/auditoria';
                break;
            case 'IE':
                return 'Benefício bloqueado para empréstimo pelo beneficiário';
                break;
            case 'IF':
                return 'Benefício bloqueado para empréstimo por TBM';
                break;
            case 'IG':
                return 'Benefício está em fase de concessão de PA ou desdobramento';
                break;
            case 'IH':
                return 'Benefício cessado por óbito';
                break;
            case 'II':
                return 'Benefício cessado por fraude';
                break;
            case 'IJ':
                return 'Benefício cessado por concessão de outro benefício';
                break;
            case 'IK':
                return 'Benefício cessado: estatutário transferido para órgão de origem';
                break;
            case 'IL':
                return 'Empréstimo suspenso pela AP';
                break;
            case 'IM':
                return 'Empréstimo cancelado pelo banco';
                break;
            case 'IN':
                return 'Crédito transformado em PAB';
                break;
            case 'IO':
                return 'Término da consignação foi alterado';
                break;
            case 'IP':
                return 'Fim do empréstimo ocorreu durante período de suspensão ou concessão';
                break;
            case 'IQ':
                return 'Empréstimo suspenso pelo banco';
                break;
            case 'IR':
                return 'Não averbação de contrato – quantidade de parcelas/competências informadas ultrapassou a data limite da extinção de cota do
                dependente titular de benefícios';
                break;
            case 'TA':
                return 'Lote Não Aceito - Totais do Lote com Diferença';
                break;
            case 'YA':
                return 'Título Não Encontrado';
                break;
            case 'YB':
                return 'Identificador Registro Opcional Inválido';
                break;
            case 'YC':
                return 'Código Padrão Inválido';
                break;
            case 'YD':
                return 'Código de Ocorrência Inválido';
                break;
            case 'YE':
                return 'Complemento de Ocorrência Inválido';
                break;
            case 'YF':
                return 'Alegação já Informada';
                break;
            case 'ZA':
                return 'Agência / Conta do Favorecido Substituída Observação: As ocorrências iniciadas com [ZA] tem caráter informativo para o cliente';
                break;
            case 'ZB':
                return 'Divergência entre o primeiro e último nome do beneficiário versus primeiro e último nome na Receita Federal';
                break;
            case 'ZC':
                return 'Confirmação de Antecipação de Valor';
                break;
            case 'ZD':
                return 'Antecipação parcial de valor';
                break;
            case 'ZE':
                return 'Título bloqueado na base';
                break;
            case 'ZF':
                return 'Sistema em contingência – título valor maior que referência';
                break;
            case 'ZG':
                return 'Sistema em contingência – título vencido';
                break;
            case 'ZH':
                return 'Sistema em contingência – título indexado';
                break;
            case 'ZI':
                return 'Beneficiário divergente';
                break;
            case 'ZJ':
                return 'Limite de pagamentos parciais excedido';
                break;
            case 'ZK':
                return 'Boleto já liquidado';
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
            case 'BD':
                return true;
                break;
            default:
                return false;
        }
    }
}

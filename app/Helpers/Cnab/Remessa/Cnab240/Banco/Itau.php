<?php
/**
 * Created by PhpStorm.
 * User: simetriatecnologia
 * Date: 15/09/16
 * Time: 14:02
 */

namespace App\Helpers\Cnab\Remessa\Cnab240\Banco;

use App\Helpers\CalculoDV;
use App\Helpers\Cnab\Remessa\Cnab240\AbstractRemessa;
use App\Helpers\Contracts\Boleto\Boleto as BoletoContract;
use App\Helpers\Contracts\Cnab\Remessa as RemessaContract;
use App\Helpers\Util;

class Itau extends AbstractRemessa implements RemessaContract
{
    const OCORRENCIA_REMESSA = '01';
    const OCORRENCIA_PEDIDO_BAIXA = '02';
    const OCORRENCIA_CONCESSAO_ABATIMENTO = '04';
    const OCORRENCIA_CANC_ABATIMENTO = '05';
    const OCORRENCIA_ALT_VENCIMENTO = '06';
    const OCORRENCIA_PROTESTAR = '09';
    const OCORRENCIA_NAO_PROTESTAR = '10';
    const OCORRENCIA_SUSTAR_PROTESTO = '18';
    const OCORRENCIA_ALT_OUTROS_DADOS = '31';
    const OCORRENCIA_NAO_CONCORDA_SACADO = '38';
    const OCORRENCIA_DISPENSA_JUROS = '47';
    const OCORRENCIA_ALT_DADOS_EXTRAS = '49';
    const OCORRENCIA_ENT_NEGATIVACAO = '66';
    const OCORRENCIA_NAO_NEGATIVAR = '67';
    const OCORRENCIA_EXC_NEGATIVACAO = '68';
    const OCORRENCIA_CANC_NEGATIVACAO = '69';
    const OCORRENCIA_DESCONTAR_TITULOS_DIA = '93';

    const PROTESTO_SEM = '0';
    const PROTESTO_DIAS_CORRIDOS = '1';
    const PROTESTO_DIAS_UTEIS = '2';
    const PROTESTO_NAO_PROTESTAR = '3';
    const PROTESTO_NEGATIVAR_DIAS_CORRIDOS = '7';
    const PROTESTO_NAO_NEGATIVAR = '8';

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = BoletoContract::COD_BANCO_ITAU;


    /**
     * Define as carteiras disponíveis para cada banco
     *
     * @var array
     */
    protected $carteiras = ['112', '115', '188', '109', '121', '175'];

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function addBoleto(BoletoContract $boleto)
    {
        $this->boletos[] = $boleto;
        //$this->segmentoA($boleto);
        $this->segmentoJ($boleto);
        //$this->segmentoA($boleto);
        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    protected function segmentoA(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'A');
        $this->add(15, 17, Util::tipoDeMovimentoPorDocumento($boleto->getPagador()->getDocumento()));
        $this->add(18, 20, '009');
        $this->add(21, 23, Util::onlyNumbers($boleto->getCodigoBanco()));
        $this->add(24, 28, Util::formatCnab('9', $boleto->getAgencia(), 5));
        $this->add(29, 29, '');
        $this->add(30, 41, Util::formatCnab('9', $boleto->getConta(), 12));
        $this->add(43, 43, Util::formatCnab('9', $boleto->getContaDv(), 1));
        $this->add(44, 73, Util::formatCnab('X', $boleto->getPagador()->getNome(), 30));
        $this->add(74, 93, Util::formatCnab('X', $boleto->getNumeroDocumento(), 20));//número do documento atribuído a empresa VEERIFICAR
        $this->add(94, 101, Util::formatCnab('9', $boleto->getDataVencimento()->format('dmY'), 8));
        $this->add(102, 104, Util::formatCnab('X', 'REA', 3));
        $this->add(105, 112, Util::formatCnab('9', '', 8));
        $this->add(113, 114, Util::formatCnab('X', $boleto->getTransferTypeIdentification(), 2));//terminar aqui
        $this->add(115, 119, Util::formatCnab('9', '', 5));
        $this->add(120, 134, Util::formatCnab('9', $boleto->getValor(), 15));
        $this->add(135, 149, Util::formatCnab('X', '', 15));
        $this->add(150, 154, Util::formatCnab('X', '', 5));
        $this->add(155, 181, Util::formatCnab('9', '', 27)); //dados retorno
        $this->add(182, 197, Util::formatCnab('X', '', 15));
        $this->add(198, 203, Util::formatCnab('9', '', 6));
        $this->add(204, 217, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getDocumento()), 14));
        $this->add(218, 229, '');
        $this->add(230, 230, 0);
        return $this;
    }

    protected function segmentoJ(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //ok
        $this->add(4, 7, '0001'); //ok
        $this->add(8, 8, '3'); //ok
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5)); //ok
        $this->add(14, 14, 'J'); //ok
        $this->add(15, 17, Util::tipoDeMovimentoPorDocumento($boleto->getPagador()->getDocumento())); //ok
        $this->add(18, 20, Util::formatCnab('9', Util::codigoBancoFavorecidoBoleto($boleto->getCodigoDeBarra()), 3));
        $this->add(21, 21, Util::formatCnab('9', Util::codigoMoedaBoleto($boleto->getCodigoDeBarra()), 1));
        $this->add(22, 22, Util::formatCnab('9', Util::dvBoleto($boleto->getCodigoDeBarra()), 1));
        $this->add(23, 26, Util::formatCnab('9', Util::fatorVencimentoBoleto($boleto->getCodigoDeBarra()), 4));
        $this->add(27, 36, Util::formatCnab('9', Util::valorBoleto($boleto->getCodigoDeBarra()), 10));
        $this->add(37, 61, Util::formatCnab('9', Util::campoLivreBoleto($boleto->getCodigoDeBarra()), 25));
        $this->add(62, 91, Util::formatCnab('X', $boleto->getPagador()->getNome(), 30));
        $this->add(92, 99, Util::formatCnab('9', $boleto->getDataVencimento()->format('dmY'), 8));
        $this->add(100, 114, Util::formatCnab('9', $boleto->getValor(), 15));
        $this->add(115, 129, Util::formatCnab('9', $boleto->getDesconto(), 15));
        $this->add(130, 144, Util::formatCnab('9', $boleto->getMulta(), 15));
        $this->add(145, 152, Util::formatCnab('9', $boleto->getDataPagamento()->format('dmY'), 8));
        $this->add(153, 167, Util::formatCnab('9', $boleto->getValorPagamento(), 15));
        $this->add(168, 182, Util::formatCnab('9', '', 15));
        $this->add(183, 202, Util::formatCnab('X', $boleto->getNumeroDocumento(), 20));
        $this->add(203, 215, Util::formatCnab('X', '', 13));
        $this->add(216, 240, Util::formatCnab('X', '', 25));
        return $this;
    }
    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    protected function segmentoP(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'P');
        $this->add(15, 15, '');
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_OUTROS_DADOS);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO_DATA) {
            $this->add(16, 17, self::OCORRENCIA_ALT_VENCIMENTO);
        }
        if ($boleto->getStatus() == $boleto::STATUS_CUSTOM) {
            $this->add(16, 17, sprintf('%2.02s', $boleto->getComando()));
        }
        $this->add(18, 18, '0');
        $this->add(19, 22, Util::formatCnab('9', $this->getAgencia(), 4));
        $this->add(23, 23, '');
        $this->add(24, 30, '0000000');
        $this->add(31, 35, Util::formatCnab('9', $this->getConta(), 5));
        $this->add(36, 36, '');
        $this->add(37, 37, CalculoDV::itauContaCorrente($this->getAgencia(), $this->getConta()));
        $this->add(38, 40, Util::formatCnab('9', $this->getCarteira(), 3));
        $this->add(41, 49, Util::formatCnab('9', $boleto->getNossoNumero(), 9));
        $this->add(50, 57, '');
        $this->add(58, 62, '00000');
        $this->add(63, 72, Util::formatCnab('9', $boleto->getNumeroDocumento(), 10));
        $this->add(73, 77, '');
        $this->add(78, 85, $boleto->getDataVencimento()->format('dmY'));
        $this->add(86, 100, Util::formatCnab('9', $boleto->getValor(), 15, 2));
        $this->add(101, 105, '00000');
        $this->add(106, 106, '0');
        $this->add(107, 108, Util::formatCnab('9', $boleto->getEspecieDocCodigo(), 2));
        $this->add(109, 109, Util::formatCnab('9', $boleto->getAceite(), 1));
        $this->add(110, 117, $boleto->getDataDocumento()->format('dmY'));
        $this->add(118, 118, '0');
        $this->add(119, 126, $boleto->getDataVencimento()->format('dmY'));
        $this->add(127, 141, Util::formatCnab('9', $boleto->getMoraDia(), 15, 2)); //Valor da mora/dia ou Taxa mensal
        $this->add(142, 142, '0');
        $this->add(143, 150, $boleto->getDesconto() > 0 ? $boleto->getDataDesconto()->format('dmY') : '00000000');
        $this->add(151, 165, Util::formatCnab('9', $boleto->getDesconto(), 15, 2));
        $this->add(166, 180, Util::formatCnab('9', 0, 15, 2));
        $this->add(181, 195, Util::formatCnab('9', 0, 15, 2));
        $this->add(196, 220, Util::formatCnab('X', $boleto->getNumeroControle(), 25));
        $this->add(221, 221, self::PROTESTO_SEM);
        if ($boleto->getDiasProtesto() > 0) {
            $this->add(221, 221, self::PROTESTO_DIAS_UTEIS);
        }
        $this->add(222, 223, Util::formatCnab('9', $boleto->getDiasProtesto(), 2));
        $this->add(224, 224, '0');
        $this->add(225, 226, '00');
        $this->add(227, 239, '0000000000000');
        $this->add(240, 240, '');

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function segmentoQ(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'Q');
        $this->add(15, 15, '');
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_OUTROS_DADOS);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO_DATA) {
            $this->add(16, 17, self::OCORRENCIA_ALT_VENCIMENTO);
        }
        if ($boleto->getStatus() == $boleto::STATUS_CUSTOM) {
            $this->add(16, 17, sprintf('%2.02s', $boleto->getComando()));
        }
        $this->add(18, 18, strlen(Util::onlyNumbers($boleto->getPagador()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 33, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getDocumento()), 15));
        $this->add(34, 63, Util::formatCnab('X', $boleto->getPagador()->getNome(), 30));
        $this->add(64, 73, '');
        $this->add(74, 113, Util::formatCnab('X', $boleto->getPagador()->getEndereco(), 40));
        $this->add(114, 128, Util::formatCnab('X', $boleto->getPagador()->getBairro(), 15));
        $this->add(129, 133, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getCep()), 5));
        $this->add(134, 136, Util::formatCnab('9', Util::onlyNumbers(substr($boleto->getPagador()->getCep(), 6, 9)), 3));
        $this->add(137, 151, Util::formatCnab('X', $boleto->getPagador()->getCidade(), 15));
        $this->add(152, 153, Util::formatCnab('X', $boleto->getPagador()->getUf(), 2));
        $this->add(154, 154, '0');
        $this->add(155, 169, '000000000000000');
        $this->add(170, 199, '');
        $this->add(200, 209, '');
        $this->add(210, 212, '000');
        $this->add(213, 240, '');

        if($boleto->getSacadorAvalista()) {
            $this->add(154, 154, strlen(Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento())) == 14 ? 2 : 1);
            $this->add(155, 169, Util::formatCnab('9', Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento()), 15));
            $this->add(170, 199, Util::formatCnab('X', $boleto->getSacadorAvalista()->getNome(), 30));
        }

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function segmentoY(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'Y');
        $this->add(15, 15, '');
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_OUTROS_DADOS);
        }
        $this->add(18, 19, '01');
        $this->add(20, 20, strlen(Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento())) == 14 ? 2 : 1);
        $this->add(21, 35, Util::formatCnab('9', Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento()), 15));
        $this->add(36, 75, Util::formatCnab('X', $boleto->getSacadorAvalista()->getNome(), 40));
        $this->add(76, 115, Util::formatCnab('X', $boleto->getSacadorAvalista()->getEndereco(), 40));
        $this->add(116, 130, Util::formatCnab('X', $boleto->getSacadorAvalista()->getBairro(), 15));
        $this->add(131, 138, Util::formatCnab('9', Util::onlyNumbers($boleto->getSacadorAvalista()->getCep()), 8));
        $this->add(139, 153, Util::formatCnab('X', $boleto->getSacadorAvalista()->getCidade(), 15));
        $this->add(154, 155, Util::formatCnab('X', $boleto->getSacadorAvalista()->getUf(), 2));
        $this->add(156, 240, '');

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function header()
    {
        $this->iniciaHeader();

        /**
         * HEADER DE ARQUIVO
         */
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0000');
        $this->add(8, 8, '0');
        $this->add(9, 14, '');
        $this->add(15, 17, '081');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 32, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 14));
        $this->add(33, 52, '');
        $this->add(53, 53, 0);
        $this->add(54, 57, Util::formatCnab('9', $this->getAgencia(), 4));
        $this->add(58, 58, '');
        $this->add(59, 65, '0000000');
        $this->add(66, 70, Util::formatCnab('9', $this->getConta(), 5));
        $this->add(71, 71, '');
        $this->add(72, 72, CalculoDV::itauContaCorrente($this->getAgencia(), $this->getConta()));
        $this->add(73, 102, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(103, 132, Util::formatCnab('X', 'BANCO ITAU SA', 30));
        $this->add(133, 142, '');
        $this->add(143, 143, 1);
        $this->add(144, 151, $this->getDataRemessa('dmY'));
        $this->add(152, 157, date('His'));
        $this->add(158, 163, '000000');
        $this->add(164, 166, '000');
        $this->add(167, 171, '00000');
        $this->add(172, 240, '');
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function headerLote()
    {
        $this->iniciaHeaderLote();

        /**
         * HEADER DE LOTE
         */
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '1');
        $this->add(9, 9, 'C');
        $this->add(10, 11, '20');
        $this->add(12, 13, '45');
        $this->add(14, 16, '040');
        $this->add(17, 17, '');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 32, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 14));
        $this->add(33, 52, '');
        $this->add(53, 53, '0');
        $this->add(54, 57, Util::formatCnab('9', $this->getAgencia(), 4));
        $this->add(58, 58, '');
        $this->add(59, 65, '0000000');
        $this->add(66, 70, Util::formatCnab('9', $this->getConta(), 5));
        $this->add(71, 71, '');
        $this->add(72, 72, CalculoDV::itauContaCorrente($this->getAgencia(), $this->getConta()));
        $this->add(73, 102, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(103, 142, '');
        $this->add(143, 172, Util::formatCnab('X',$this->getBeneficiario()->getEndereco(), 30));
        $this->add(173, 177, Util::formatCnab('9', $this->getBeneficiario()->getNumero(), 5));
        $this->add(178, 192, Util::formatCnab('X',$this->getBeneficiario()->getComplemento(), 15));
        $this->add(193, 212, Util::formatCnab('X',$this->getBeneficiario()->getCidade(), 20));
        $this->add(213, 220, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getCep()), 8));
        $this->add(221, 222, strtoupper($this->getBeneficiario()->getUf()));
        $this->add(224, 240, '');
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function trailerLote()
    {
        $this->iniciaTrailerLote();

        $valor = array_reduce($this->boletos, function($valor, $boleto) {
            return $valor + $boleto->getValor();
        }, 0);

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '5');
        $this->add(9, 17, '');
        $this->add(18, 23, Util::formatCnab('9', count($this->boletos), 6));
        $this->add(24, 41, Util::formatCnab('9', $valor, 18 , 2));
        $this->add(42, 59, Util::formatCnab('9', 0, 18));
        $this->add(60, 240, Util::formatCnab('X', '', 171));
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function trailer()
    {
        $this->iniciaTrailer();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '9999');
        $this->add(8, 8, '9');
        $this->add(9, 17, '');
        $this->add(18, 23, Util::formatCnab('9', 1, 6));
        $this->add(24, 29, Util::formatCnab('9', $this->getCount(), 6));
        $this->add(30, 240, '');
        return $this;
    }
}

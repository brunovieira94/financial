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

class Bb extends AbstractRemessa implements RemessaContract
{

    const OCORRENCIA_REMESSA = '01';
    const OCORRENCIA_PEDIDO_BAIXA = '02';
    const OCORRENCIA_CONCESSAO_ABATIMENTO = '04';
    const OCORRENCIA_CANC_ABATIMENTO = '05';
    const OCORRENCIA_ALT_VENCIMENTO = '06';
    const OCORRENCIA_CONCESSAO_DESCONTO = '07';
    const OCORRENCIA_CANC_DESCONTO = '08';
    const OCORRENCIA_PROTESTAR = '09';
    const OCORRENCIA_CANC_PROTESTO = '10';
    const OCORRENCIA_RECUSA_SACADO = '30';
    const OCORRENCIA_ALT_OUTROS_DADOS = '31';
    const OCORRENCIA_ALT_MODALIDADE = '40';

    const PROTESTO_SEM = '0';
    const PROTESTO_DIAS_CORRIDOS = '1';
    const PROTESTO_DIAS_UTEIS = '2';
    const PROTESTO_NAO_PROTESTAR = '3';

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->addCampoObrigatorio('convenio', 'convenioLider', 'variacaoCarteira');
    }

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = BoletoContract::COD_BANCO_BB;

    /**
     * Define as carteiras disponíveis para cada banco
     *
     * @var array
     */
    protected $carteiras = ['11', '12', '17', '31', '51'];

    /**
     * Convenio com o banco
     *
     * @var string
     */
    protected $convenio;

    /**
     * Convenio lider com o banco
     *
     * @var string
     */
    protected $convenioLider;

    /**
     * Variação da carteira
     *
     * @var string
     */
    protected $variacaoCarteira;

    protected $codigoFormaPagamento;

    protected $tipoSeguimento;

    /**
     * @return mixed
     */
    public function getConvenio()
    {
        return $this->convenio;
    }

    /**
     * @param mixed $convenio
     *
     * @return Bb
     */
    public function setConvenio($convenio)
    {
        $this->convenio = ltrim($convenio, 0);

        return $this;
    }

    public function getCodigoFormaPagamento()
    {
        return $this->codigoFormaPagamento;
    }

    public function setCodigoFormaPagamento($codigoFormaPagamento)
    {
        $this->codigoFormaPagamento = $codigoFormaPagamento;
        return $this;
    }

    public function getTipoSeguimento()
    {
        return $this->tipoSeguimento;
    }

    public function setTipoSeguimento($tipoSeguimento)
    {
        $this->tipoSeguimento = $tipoSeguimento;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getConvenioLider()
    {
        return $this->convenioLider ? $this->convenioLider : $this->getConvenio();
    }

    /**
     * @param mixed $convenioLider
     *
     * @return Bb
     */
    public function setConvenioLider($convenioLider)
    {
        $this->convenioLider = $convenioLider;

        return $this;
    }

    /**
     * Retorna variação da carteira
     *
     * @return string
     */
    public function getVariacaoCarteira()
    {
        return $this->variacaoCarteira;
    }

    /**
     * Seta a variação da carteira
     *
     * @param string $variacaoCarteira
     *
     * @return Bb
     */
    public function setVariacaoCarteira($variacaoCarteira)
    {
        $this->variacaoCarteira = $variacaoCarteira;

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function addBoleto(BoletoContract $boleto)
    {
        $this->boletos[] = $boleto;

        if($this->tipoSeguimento == 1){
            $this->segmentoJ($boleto);
        } else {
            $this->segmentoA($boleto);
        }
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
        $this->add(15, 15, '0'); //Tipo de Movimento
        $this->add(16, 17, '00'); //cod Tipo de Movimento
        $this->add(18, 20, $this->codigoFormaPagamento == 42 || $this->codigoFormaPagamento == 41 ? '018' : '000'); // validar
        $this->add(21, 23, Util::onlyNumbers($boleto->getCodigoBanco()));
        $this->add(24, 28, Util::formatCnab('9', $boleto->getAgencia(), 5));
        $this->add(29, 29, Util::formatCnab('X', $boleto->getAgenciaDv(), 1));
        $this->add(30, 41, Util::formatCnab('9', $boleto->getConta(), 12));
        $this->add(42, 42, Util::formatCnab('9', $boleto->getContaDv(), 1));
        $this->add(42, 42, '');  //validar
        $this->add(44, 73, Util::formatCnab('X', $boleto->getPagador()->getNome(), 30));
        $this->add(74, 93, Util::formatCnab('X', $boleto->getNumeroDocumento(), 20));//número do documento atribuído a empresa VEERIFICAR
        $this->add(94, 101, Util::formatCnab('9', $boleto->getDataVencimento()->format('dmY'), 8));
        $this->add(102, 104, Util::formatCnab('X', 'BRL', 3));
        $this->add(105, 119, Util::formatCnab('9', '000000000000000', 15));
        $this->add(120, 134, Util::formatCnab('9', $boleto->getValor(), 15));
        $this->add(135, 154, Util::formatCnab('X', '', 20));
        $this->add(155, 162, Util::formatCnab('9', '', 8));
        $this->add(163, 177, Util::formatCnab('9', '', 15)); //dados retorno
        $this->add(178, 217, Util::formatCnab('X', '', 40));
        $this->add(218, 219, Util::formatCnab('X', '', 2));
        $this->add(220, 224, Util::formatCnab('X', '', 5));
        $this->add(225, 226, Util::formatCnab('X', '', 2));
        $this->add(227, 229, Util::formatCnab('X', '', 3));
        $this->add(230, 230, Util::formatCnab('9', '', 1));
        $this->add(231, 240, Util::formatCnab('X', '', 10));
        $this->segmentoB($boleto);
        return $this;
    }

    protected function segmentoB(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //ok
        $this->add(4, 7, '0001'); //ok
        $this->add(8, 8, '3'); //ok
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5)); //ok
        $this->add(14, 14, 'B'); //ok
        $this->add(15, 17, ''); //cod Tipo de Movimento
        $this->add(18, 18, strlen(Util::onlyNumbers($boleto->getPagador()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 32, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getDocumento()), 14));
        $this->add(33, 62, '');
        $this->add(63, 67, '00000');
        $this->add(68, 82, '');
        $this->add(83, 97, '');
        $this->add(98, 117, '');
        $this->add(118, 122, '00000');
        $this->add(123, 125, '');
        $this->add(126, 127, '');
        $this->add(128, 135, '00000000');
        $this->add(136, 150, '000000000000000');
        $this->add(151, 165, '000000000000000');
        $this->add(166, 180, '000000000000000');
        $this->add(181, 195, '000000000000000');
        $this->add(196, 210, '000000000000000');
        $this->add(211, 225, '');
        $this->add(226, 226, '0');
        $this->add(227, 232, '000000');
        $this->add(233, 240, '');
    }


    protected function segmentoJ(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //ok
        $this->add(4, 7, '0001'); //ok
        $this->add(8, 8, '3'); //ok
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5)); //ok
        $this->add(14, 14, 'J'); //ok
        $this->add(15, 15, '0'); //Tipo de Movimento
        $this->add(16, 17, '00'); //cod Tipo de Movimento
        $this->add(18, 61, Util::formatCnab('9', Util::codigoBarrasBB(Util::onlyNumbers($boleto->getCodigoDeBarra())), 44)); //ok;
        $this->add(62, 91, Util::formatCnab('X', $boleto->getPagador()->getNome(), 30));
        $this->add(92, 99, Util::formatCnab('9', $boleto->getDataVencimento()->format('dmY'), 8));
        $this->add(100, 114, Util::formatCnab('9', $boleto->getValor(), 15));
        $this->add(115, 129, Util::formatCnab('9', $boleto->getDesconto(), 15));
        $this->add(130, 144, Util::formatCnab('9', $boleto->getMulta(), 15));
        $this->add(145, 152, Util::formatCnab('9', $boleto->getDataPagamento()->format('dmY'), 8));
        $this->add(153, 167, Util::formatCnab('9', $boleto->getValorPagamento(), 15));
        $this->add(168, 182, Util::formatCnab('9', '', 15));
        $this->add(183, 202, Util::formatCnab('X', $boleto->getNumeroDocumento(), 20));
        $this->add(203, 222, Util::formatCnab('X', '', 20));
        $this->add(223, 224, Util::formatCnab('9', '09', 2));
        $this->add(225, 230, Util::formatCnab('X', '', 6));
        $this->add(231, 240, Util::formatCnab('X', '0000000000', 10));
        $this->segmentoJ52($boleto);
        return $this;
    }

    protected function segmentoJ52(BoletoContract $boleto)
    {

        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco())); //ok
        $this->add(4, 7, '0001'); //ok
        $this->add(8, 8, '3'); //ok
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5)); //ok
        $this->add(14, 14, 'J'); //ok
        $this->add(15, 15, '0'); //Tipo de Movimento
        $this->add(16, 17, '00'); //cod Tipo de Movimento
        $this->add(18, 19, '52'); //ok
        $this->add(20, 20, strlen(Util::onlyNumbers($boleto->getPagador()->getDocumento())) == 14 ? 2 : 1);
        $this->add(21, 35, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getDocumento()), 15));
        $this->add(36, 75, '');
        $this->add(76, 76, strlen(Util::onlyNumbers($boleto->getPagador()->getDocumento())) == 14 ? 2 : 1);
        $this->add(77, 91, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getDocumento()), 15));
        $this->add(92, 131, '');
        $this->add(132, 132, '0');
        $this->add(132, 132, '0');
        $this->add(133, 147, '000000000000000');
        $this->add(148, 240, '');
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
        $this->add(34, 73, Util::formatCnab('X', $boleto->getPagador()->getNome(), 40));
        $this->add(74, 113, Util::formatCnab('X', $boleto->getPagador()->getEndereco(), 40));
        $this->add(114, 128, Util::formatCnab('X', $boleto->getPagador()->getBairro(), 15));
        $this->add(129, 133, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getCep()), 5));
        $this->add(134, 136, Util::formatCnab('9', Util::onlyNumbers(substr($boleto->getPagador()->getCep(), 6, 9)), 3));
        $this->add(137, 151, Util::formatCnab('X', $boleto->getPagador()->getCidade(), 15));
        $this->add(152, 153, Util::formatCnab('X', $boleto->getPagador()->getUf(), 2));
        $this->add(154, 154, '0');
        $this->add(155, 169, '000000000000000');
        $this->add(170, 209, '');
        $this->add(210, 212, '000');
        $this->add(213, 240, '');

        if($boleto->getSacadorAvalista()) {
            $this->add(154, 154, strlen(Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento())) == 14 ? 2 : 1);
            $this->add(155, 169, Util::formatCnab('9', Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento()), 15));
            $this->add(170, 209, Util::formatCnab('X', $boleto->getSacadorAvalista()->getNome(), 30));
        }

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function segmentoR(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'R');
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
        $this->add(19, 26, '00000000');
        $this->add(27, 41, '000000000000000');
        $this->add(42, 42, '0');
        $this->add(43, 50, '00000000');
        $this->add(51, 65, '000000000000000');
        $this->add(66, 66, $boleto->getMulta() > 0 ? '2' : '0'); //0 = ISENTO | 1 = VALOR FIXO | 2 = PERCENTUAL
        $this->add(67, 74, $boleto->getDataVencimento()->format('dmY'));
        $this->add(75, 89, Util::formatCnab('9', $boleto->getMulta(), 15, 2));  //2,20 = 0000000000220
        $this->add(90, 199, '');
        $this->add(200, 207, '00000000');
        $this->add(208, 210, '000');
        $this->add(211, 215, '00000');
        $this->add(216, 216, '');
        $this->add(217, 228, '000000000000');
        $this->add(229, 230, '');
        $this->add(231, 231, '0');
        $this->add(232, 240, '');

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function header()
    {
        $this->iniciaHeader();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0000');
        $this->add(8, 8, '0');
        $this->add(9, 17, '');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 32, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 14));
        $this->add(33, 41, Util::formatCnab('9', Util::onlyNumbers($this->getConvenio()), 9));
        $this->add(42, 45, '0126');
        $this->add(46, 50, Util::formatCnab('X', '', 5));
        //$this->add(51, 52, 'TS'); //ARQUIVO DE TESTE
        $this->add(51, 52, 'TS'); // correto
        $this->add(53, 57, Util::formatCnab('9', $this->getAgencia(), 5));
        $this->add(58, 58, CalculoDV::bbAgencia($this->getAgencia()));
        $this->add(59, 70, Util::formatCnab('9', $this->getConta(), 12));
        $this->add(71, 71, CalculoDV::bbContaCorrente($this->getConta()));
        $this->add(72, 72, '0');
        $this->add(73, 102, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(103, 142, Util::formatCnab('X', 'BANCO DO BRASIL S.A.', 30));
        $this->add(133, 142, '');
        $this->add(143, 143, 1);
        $this->add(144, 151, $this->getDataRemessa('dmY'));
        $this->add(152, 157, date('His'));
        $this->add(158, 163, '000000');
        $this->add(164, 166, '084');
        $this->add(167, 171, '01600');
        $this->add(172, 211, '');
        $this->add(212, 240, '');
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function headerLote()
    {
        $this->iniciaHeaderLote();

        if($this->tipoSeguimento == 1){
            $this->headerLoteJ();
        } else {
            $this->headerLoteA();
        }
    }

    public function headerLoteA(){

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '1');
        $this->add(9, 9, 'C');
        $this->add(10, 11, '20'); //Validar
        $this->add(12, 13, Util::formatCnab('9', $this->getCodigoFormaPagamento(), 2)); // validar
        $this->add(14, 16, '043');
        $this->add(17, 17, '');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 32, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 14));
        $this->add(33, 41, Util::formatCnab('9', Util::onlyNumbers($this->getConvenio()), 9));
        $this->add(42, 45, '0126');
        $this->add(46, 50, '');
        $this->add(51, 52, 'TS');
        $this->add(53, 57, Util::formatCnab('9', $this->getAgencia(), 5));
        $this->add(58, 58, CalculoDV::bbAgencia($this->getAgencia()));
        $this->add(59, 70, Util::formatCnab('9', $this->getConta(), 12));
        $this->add(71, 71, CalculoDV::bbContaCorrente($this->getConta()));
        $this->add(72, 72, '0');
        $this->add(73, 102, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(103, 142, '');
        $this->add(143, 172, Util::formatCnab('X', '', 30));
        $this->add(173, 177, Util::formatCnab('9', '00000', 5));
        $this->add(178, 192, Util::formatCnab('X', '', 15));
        $this->add(193, 212, '');
        $this->add(213, 217, '00000');
        $this->add(218, 220, '');
        $this->add(221, 222, '');
        $this->add(223, 230, '');
        $this->add(231, 240, '0000000000');
        return $this;
    }

    public function headerLoteJ(){

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '1');
        $this->add(9, 9, 'C');
        $this->add(10, 11, '98'); //Validar
        $this->add(12, 13, '31');
        $this->add(14, 16, '042');
        $this->add(17, 17, '');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 32, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 14));
        $this->add(33, 41, Util::formatCnab('9', Util::onlyNumbers($this->getConvenio()), 9));
        $this->add(42, 45, '0126');
        $this->add(46, 50, Util::formatCnab('9', '', 5));
        $this->add(51, 52, 'TS');
        $this->add(53, 57, Util::formatCnab('9', $this->getAgencia(), 5));
        $this->add(58, 58, CalculoDV::bbAgencia($this->getAgencia()));
        $this->add(59, 70, Util::formatCnab('9', $this->getConta(), 12));
        $this->add(71, 71, CalculoDV::bbContaCorrente($this->getConta()));
        $this->add(72, 72, '0');
        $this->add(73, 102, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(103, 142, '');
        $this->add(143, 172, Util::formatCnab('X', '', 30));
        $this->add(173, 177, Util::formatCnab('9', '00000', 5));
        $this->add(178, 192, Util::formatCnab('X', '', 15));
        $this->add(193, 212, '');
        $this->add(213, 217, '00000');
        $this->add(218, 220, '');
        $this->add(221, 222, '');
        $this->add(223, 230, '');
        $this->add(231, 240, '0000000000');
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
        $this->add(18, 23, Util::formatCnab('9', count($this->boletos) + 3, 6));
        $this->add(24, 41, Util::formatCnab('9', $valor, 18 , 2));
        $this->add(42, 59, Util::formatCnab('9', 0, 18));
        $this->add(60, 65, '000000');
        $this->add(66, 230, '');
        $this->add(231, 240, '0000000000');
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
        $this->add(30, 35, '000000');
        $this->add(36, 240, ' ');

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return mixed|string
     */
    private function nossoNumero(BoletoContract $boleto) {
        $convenio = (int) Util::onlyNumbers($this->getConvenio());
        if ($convenio > 1000000) {
            return $boleto->getNossoNumero();
        }
        return $boleto->getNossoNumero() . CalculoDV::bbNossoNumero($boleto->getNossoNumero());
    }
}

<?php

namespace App\Services;

use App\Models\FormPayment;

class Utils
{
    const defaultPerPage = 20;
    const defaultOrderBy = 'id';
    const defaultOrder = 'desc';

    public static function pagination($model,$requestInfo){
        $orderBy = $requestInfo['orderBy'] ?? self::defaultOrderBy;
        $order = $requestInfo['order'] ?? self::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? self::defaultPerPage;
        return $model->orderBy($orderBy, $order)->paginate($perPage);
    }

    public static function getDeleteKeys($nestable){
        $arrayIds = [];
        foreach($nestable as $key=>$value){
            array_push($arrayIds, $nestable[$key]['id']);
            if(sizeof($nestable[$key]['children']) > 0){
                $auxArray = self::getDeleteKeys($nestable[$key]['children']);
                foreach($auxArray as $element){
                    array_push($arrayIds, $element);
                }
            }
        }
        return $arrayIds;
    }

    public static function validateDate($date, $format = 'd/m/Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public static function formatDate($date)
    {
        $date = explode('/', $date);
        $year = $date[2];
        $date[2] = $date[0];
        $date[0] = $year;
        return $date = implode('-', $date);
    }

    public static function search($model,$requestInfo,$excludeFields = null){
        $fillable = $model->getFillable();
        if ($excludeFields != null)
        {
            foreach ($fillable as $key=>$value) {
                if(in_array($fillable[$key], $excludeFields)){
                    unset($fillable[$key]);
                }
            }
        }
        $query = $model->query();
        if(array_key_exists('search', $requestInfo)){

            if (self::validateDate($requestInfo['search'], 'd/m/Y')) {
                $requestInfo['search'] = self::formatDate($requestInfo['search']);
            }
            if(array_key_exists('searchFields', $requestInfo)){
                $query->whereLike($requestInfo['searchFields'], "%{$requestInfo['search']}%");
            }
            else{
                $query->whereLike($fillable, "%{$requestInfo['search']}%");
            }
        }
        return $query;
    }

    public static function groupInstallments($installments, $bankCode){

        $groupInstallment = [];

        foreach($installments as $installment)
        {
            foreach($installment->group_payment->form_payment as $payment_form)
            {
                if($payment_form->bank_code == $bankCode)
                {
                    if($payment_form->group_form_payment_id == 2) //Default PIX group 2
                    {
                        if(array_key_exists('45', $groupInstallment))
                        {
                            array_push($groupInstallment[$payment_form->code_cnab], $installment);
                            break;
                        } else
                        {
                            $groupInstallment['45'] = [$installment];
                            break;
                        }
                    }
                    elseif($payment_form->group_form_payment_id == 1)
                    {
                        if(substr($installment->bar_code, 0, 3) == $bankCode)
                        {
                            if($payment_form->same_ownership)
                            {
                                if(array_key_exists($payment_form->code_cnab, $groupInstallment))
                                {
                                    array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                    break;
                                } else
                                {
                                    $groupInstallment[$payment_form->code_cnab] = [$installment];
                                    break;
                                }

                            }
                        }else
                        {
                            if(!$payment_form->same_ownership)
                            {
                                if(array_key_exists($payment_form->code_cnab, $groupInstallment))
                                {
                                    array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                    break;
                                } else
                                {
                                    $groupInstallment[$payment_form->code_cnab] = [$installment];
                                    break;
                                }

                            }
                        }
                    }else
                    {
                        if($installment->bank_account_provider->bank->bank_code == $bankCode)
                        {
                            if($payment_form->same_ownership)
                            {
                                if(array_key_exists($payment_form->code_cnab, $groupInstallment))
                                {
                                    array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                    break;
                                } else
                                {
                                    $groupInstallment[$payment_form->code_cnab] = [$installment];
                                    break;
                                }
                            }
                        }else
                        {
                            if(!$payment_form->same_ownership)
                            {
                                if(array_key_exists($payment_form->code_cnab, $groupInstallment))
                                {
                                    array_push($groupInstallment[$payment_form->code_cnab], $installment);
                                    break;
                                } else
                                {
                                    $groupInstallment[$payment_form->code_cnab] = [$installment];
                                    break;
                                }

                            }
                        }
                    }
                }
            }
        }
    return $groupInstallment;
    }

    public static function formatCnab($tipo, $valor, $tamanho, $dec = 0, $sFill = '')
    {
        $tipo = self::upper($tipo);
        $valor = self::upper(self::normalizeChars($valor));
        if (in_array($tipo, array('9', 9, 'N', '9L', 'NL'))) {
            if ($tipo == '9L' || $tipo == 'NL') {
                $valor = self::onlyNumbers($valor);
            }
            $left = '';
            $sFill = 0;
            $type = 's';
            $valor = ($dec > 0) ? sprintf("%.{$dec}f", $valor) : $valor;
            $valor = str_replace(array(',', '.'), '', $valor);
        } elseif (in_array($tipo, array('A', 'X'))) {
            $left = '-';
            $type = 's';
        } else {
            throw new \Exception('Tipo inválido');
        }
        return sprintf("%{$left}{$sFill}{$tamanho}{$type}", mb_substr($valor, 0, $tamanho));
    }

    public static function upper($string)
    {
        return strtr(mb_strtoupper($string), "àáâãäåæçèéêëìíîïðñòóôõö÷øùüúþÿ", "ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÜÚÞß");
    }

    public static function normalizeChars($string)
    {
        $normalizeChars = array(
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A', 'Ä' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'Eth',
            'Ñ' => 'N', 'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Ŕ' => 'R',

            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ä' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'eth',
            'ñ' => 'n', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ŕ' => 'r', 'ÿ' => 'y',

            'ß' => 'sz', 'þ' => 'thorn', 'º' => '', 'ª' => '', '°' => '',
        );
        return preg_replace('/[^0-9a-zA-Z !*\-$\(\)\[\]\{\},.;:\/\\#%&@+=]/', '', strtr($string, $normalizeChars));
    }

    public static function onlyNumbers($string)
    {
        return self::numbersOnly($string);
    }

    public static function numbersOnly($string)
    {
        return preg_replace('/[^[:digit:]]/', '', $string);
    }

    public static function centralizadoraBB($codeBank)
    {
        switch ($codeBank) {
            case 43:
                return '018';
                break;
            case 41:
                return '018';
                break;
            case 45:
                return '009';
                break;
            case 03:
                return '700';
                break;
            default:
                return '000';
        }
    }

    public static function codigoBarrasBB($linhaDigitavel)
    {
        return substr($linhaDigitavel, 0, 4) . substr($linhaDigitavel, 32, 15) . substr($linhaDigitavel, 4, 5) . substr($linhaDigitavel, 9, 6) . substr($linhaDigitavel, 16, 4) . substr($linhaDigitavel, 21, 10);
    }

    public static function identificacaoTipoTransferencia($tipoConta)
    {
        switch ($tipoConta) {
            case 0:
                return '03'; // POUPANÇA
                break;
            case 1:
                return '01'; // C CORRENTE
                break;
            case 2:
                return 'PG'; // C SALÁRIO
                break;
            case 3:
                return '04'; // PIX
                break;
        }
    }

    public static function codigoBancoFavorecidoBoleto($boleto)
    {
        return substr($boleto, 0, 3);
    }

    public static function codigoMoedaBoleto($boleto)
    {
        return substr($boleto, 3, 1);
    }

    public static function dvBoleto($boleto)
    {
        return substr($boleto, 32, 1);
    }

    public static function valorBoleto($boleto)
    {
        return substr($boleto, 37, 10);
    }

    public static function campoLivreBoleto($boleto)
    {
        $primeiroCampoLivre = substr($boleto, 4, 5);
        $segundoCampoLivre = substr($boleto, 10, 10);
        $terceiroCampoLivre = substr($boleto, 21, 10);
        return "{$primeiroCampoLivre}{$segundoCampoLivre}{$terceiroCampoLivre}";
    }

    public static function fatorVencimentoBoleto($boleto)
    {
        return substr($boleto, 33, 4);
    }
}

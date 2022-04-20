<?php

namespace App\CNABLayoutsParser\CnabParser;

final class Util
{
    public static function onlyNumbers($string)
    {
        return self::numbersOnly($string);
    }

    public static function numbersOnly($string)
    {
        return preg_replace('/[^[:digit:]]/', '', $string);
    }

    public static function codigoBarrasBB($linhaDigitavel)
    {
        return substr($linhaDigitavel, 0, 4) . substr($linhaDigitavel, 32, 15) . substr($linhaDigitavel, 4, 5) . substr($linhaDigitavel, 9, 6) . substr($linhaDigitavel, 16, 4) . substr($linhaDigitavel, 21, 10);
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
}

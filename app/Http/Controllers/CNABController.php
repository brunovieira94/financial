<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab400\Banco\Itau;

class CNABController extends Controller
{
    public function index(Request $request)
    {
        $recipient = [
            'nome'      => 'ACME',
            'endereco'  => 'Rua um, 123',
            'cep'       => '99999-999',
            'uf'        => 'UF',
            'cidade'    => 'CIDADE',
            'documento' => '99.999.999/9999-99',
        ];

        $sendArray = [
            'beneficiario' => $recipient,
            'carteira' => 109,
            'agencia' => 1111,
            'conta' => 22222,
        ];

        $send = new Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\Banco\Itau($sendArray);

        return teste;
    }
}

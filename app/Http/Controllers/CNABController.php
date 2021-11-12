<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Eduardokum;

class CNABController extends Controller
{
    public function index(Request $request)
    {
        $beneficiario = new \Eduardokum\LaravelBoleto\Pessoa(
            [
                'nome'      => 'ART VIAGENS E TURISMO LTDA',
                'endereco'  => 'RUA DOS AIMORES',
                'cep'       => '30140-071',
                'uf'        => 'MG',
                'cidade'    => 'BELO HORIZONTE',
                'documento' => '11.442.110/0001-20',
            ]
        );

        $pagador = new \Eduardokum\LaravelBoleto\Pessoa(
            [
                'nome'      => 'Alisson de S. Santos',
            ]
        );

        $pagador2 = new \Eduardokum\LaravelBoleto\Pessoa(
            [
                'nome'      => 'Bruno',
            ]
        );

        $boleto = new Eduardokum\LaravelBoleto\Boleto\Banco\Itau(
            [
                //'logo'                   => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '341.png',
                'dataVencimento'         => new \Carbon\Carbon(),
                'valor'                  => 100,
                'multa'                  => false,
                'juros'                  => false,
                'numero'                 => 1,
                'numeroDocumento'        => 1,
                'pagador'                => $pagador,
                'beneficiario'           => $beneficiario,
                'diasBaixaAutomatica'    => 2,
                'carteira'               => 112,
                'agencia'                => 1111,
                'conta'                  => 99999,
                'descricaoDemonstrativo' => ['demonstrativo 1', 'demonstrativo 2', 'demonstrativo 3'],
                'instrucoes'             => ['instrucao 1', 'instrucao 2', 'instrucao 3'],
                'aceite'                 => 'S',
                'especieDoc'             => 'DM',
            ]
        );

        $boleto2 = new Eduardokum\LaravelBoleto\Boleto\Banco\Itau(
            [
                //'logo'                   => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '341.png',
                'dataVencimento'         => new \Carbon\Carbon(),
                'valor'                  => 100,
                'multa'                  => false,
                'juros'                  => false,
                'numero'                 => 1,
                'numeroDocumento'        => 1,
                'pagador'                => $pagador2,
                'beneficiario'           => $beneficiario,
                'diasBaixaAutomatica'    => 2,
                'carteira'               => 112,
                'agencia'                => 1111,
                'conta'                  => 99999,
                'descricaoDemonstrativo' => ['demonstrativo 1', 'demonstrativo 2', 'demonstrativo 3'],
                'instrucoes'             => ['instrucao 1', 'instrucao 2', 'instrucao 3'],
                'aceite'                 => 'S',
                'especieDoc'             => 'DM',
            ]
        );

        $remessa = new \Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\Banco\Itau(
            [
                'agencia'      => 1234,
                'conta'        => 12345,
                'contaDv'      => 9,
                'carteira'     => 112,
                'beneficiario' => $beneficiario,
            ]
        );
        //$pdf = new \Eduardokum\LaravelBoleto\Boleto\Render\Pdf();
        ///$pdf->addBoleto($boleto);
        //return $pdf->gerarBoleto();
        $boletos = [];
        $boletos[] = $boleto;
        $boletos[] = $boleto2;
        $remessa->addBoletos($boletos);
        return $remessa->save('/var/www/html/storage' . DIRECTORY_SEPARATOR . 'itau.txt');
    }
}

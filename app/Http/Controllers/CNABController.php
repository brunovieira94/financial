<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Eduardokum;

class CNABController extends Controller
{
    public function shipping(Request $request)
    {
        $beneficiario = new \Eduardokum\LaravelBoleto\Pessoa(
            [
                'nome'      => 'ART VIAGENS E TURISMO LTDA',
                'endereco'  => 'RUA DOS AIMORES',
                'cep'       => '30140-071',
                'uf'        => 'MG',
                'cidade'    => 'BELO HORIZONTE',
                'documento' => '11.442.110/0001-20',
                'numero' => '0100114',
                'complemento' => 'ANDAR',
            ]
        );

        $pagador = new \Eduardokum\LaravelBoleto\Pessoa(
            [
                'nome'      => 'Alisson de S. Santos',
                'endereco'  => 'Av. Brasília',
                'cep'       => '331700-000',
                'uf'        => 'MG',
                'cidade'    => 'Santa Luzia',
                'documento' => '136.129.866-94',
                'numero' => '5850',
                'complemento' => 'Ao lado da mercearia',
            ]
        );

        $boleto = new Eduardokum\LaravelBoleto\Boleto\Banco\Itau(
            [
                //'logo'                   => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '341.png',
                'dataVencimento'         => new \Carbon\Carbon(),
                'valor'                  => 100.50,
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
                'contaDv'                  => 5,
                'descricaoDemonstrativo' => ['demonstrativo 1', 'demonstrativo 2', 'demonstrativo 3'],
                'instrucoes'             => ['instrucao 1', 'instrucao 2', 'instrucao 3'],
                'aceite'                 => 'S',
                'especieDoc'             => 'DM',
            ]
        );

        $remessa = new \Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\Banco\Itau(
            [
                'agencia'      => 1403,
                'conta'        => 970,
                'contaDv'      => 5,
                'carteira'     => 112,
                'beneficiario' => $beneficiario,
            ]
        );
        $remessa->addBoleto($boleto);
        return $remessa->save('/var/www/html/storage' . DIRECTORY_SEPARATOR . 'itau.txt');
    }

    public function return(Request $request) {

        $returnFile = $request->file('return-file');

        $processArchive = new \Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\Banco\Itau($returnFile);
        $processArchive->processar();


        $teste = $processArchive->getDetalhes();
        dd($teste[1]);
        return 'teste';
    }

}

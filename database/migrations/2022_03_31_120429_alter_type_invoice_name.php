<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterTypeInvoiceName extends Migration
{
    const items =
    [
        [
            'oldName' => 'Nota Fiscal Avulsa a Consumidor Final',
            'newName' => 'NOTA FISCAL DE VENDA/SAIDA AO CONSUMIDOR',
        ],
        [
            'oldName' => 'Nota Fiscal Eletrônica (NF-e), modelo 55',
            'newName' => 'DOCUMENTO AUXILIAR DA NOTA FISCAL ELETRÔNICA',
        ],
        [
            'oldName' => 'Nota Fiscal Avulsa Eletrônica - NFA-e, modelo 55',
            'newName' => 'NOTA FISCAL AVULSA',
        ],
        [
            'oldName' => 'Conhecimento de Transporte Eletrônico (CT-e), modelo 57',
            'newName' => 'DOCUMENTO AUXILIAR DE CONHECIMENTO DE TRANSPORTE ELETRÔNICO',
        ],
        [
            'oldName' => 'Manifesto Eletrônico de Documentos Fiscais (MDF-e), modelo 58',
            'newName' => 'DAMDFE-DOCUMENTO AUXILIAR DE MANIFESTO ELETRÔNICO DE DOCUMENTOS FISCAIS',
        ],
        [
            'oldName' => 'Conhecimento de Transporte Eletrônico para Outros Serviços (CT-e OS), modelo 67',
            'newName' => 'DOCUMENTO AUXILIAR DE CONHECIMENTO DE TRANSPORTE ELETRÔNICO',
        ],
        [
            'oldName' => 'Bilhete de Passagem Eletrônico - BP-e -, modelo 63',
            'newName' => 'DOCUMENTO AUXILIAR DE BILHETE DE PASSAGEM ELETRÔNICO',
        ],
        [
            'oldName' => 'Nota Fiscal de Consumidor Eletrônica - NFC-e -, modelo 65',
            'newName' => 'NFCe - DOCUMENTO AUXILIAR DE NOTA FISCAL DE CONSUMIDOR ELETRÔNICA',
        ],
        [
            'oldName' => 'Guia de Transporte de Valores Eletrônica - GTV-e, modelo 64',
            'newName' => 'GUIA DE TRANSPORTE DE VALORES(GTV)',
        ],
        [
            'oldName' => 'Nota Fiscal, modelo 1 ou 1-A',
            'newName' => 'NOTA FISCAL ELETRÔNICA DE SERVIÇO',
        ],
        [
            'oldName' => 'Nota Fiscal de Venda a Consumidor, modelo 2',
            'newName' => 'NOTA FISCAL DE VENDA AO CONSUMIDOR',
        ],
        [
            'oldName' => 'Cupom Fiscal emitido por equipamento Emissor de Cupom Fiscal (ECF)',
            'newName' => 'CUPOM FISCAL',
        ],
        [
            'oldName' => 'Nota Fiscal/Conta de Energia Elétrica, modelo 6',
            'newName' => 'NOTA FISCAL- CONTA DE ENERGIA ELÉTRICA SERIE U',
        ],
        [
            'oldName' => 'Ordem de Coleta de Cargas, modelo 20',
            'newName' => 'ORDEM DE COLETA DE CARGA - ATUAL CTE',
        ],
        [
            'oldName' => 'Nota Fiscal de Serviço de Comunicação, modelo 21',
            'newName' => 'NOTA FISCAL DE SERVIÇO DE COMUNICAÇÃO',
        ],
        [
            'oldName' => 'Nota Fiscal de Serviço de Telecomunicações, modelo 22',
            'newName' => 'NOTA FISCAL DE SERVIÇOS DE TELECOMUNICAÇÕES',
        ],
        [
            'oldName' => 'Despacho de Cargas em Lotação',
            'newName' => 'CTE - ANTIGO DESPACHO DE CARGAS',
        ],
        [
            'oldName' => 'Romaneio',
            'newName' => 'ROMANEIO DE CARGA',
        ],
        [
            'oldName' => 'Nota Fiscal Avulsa',
            'newName' => 'NOTA FISCAL AVULSA',
        ],
        [
            'oldName' => 'Guia de Transporte de Valores',
            'newName' => 'GUIA DE TRANSPORTE DE VALORES(GTV)',
        ],

    ];

    public function up()
    {
        foreach(self::items as $item)
        {
            DB::statement(
                'UPDATE payment_requests
                 SET invoice_type = "' . $item['newName'] . '"
                 WHERE invoice_type = "'. $item['oldName'] .'";'
            );
        }
    }

    public function down()
    {
        foreach(self::items as $item)
        {
            DB::statement(
                'UPDATE payment_requests
                 SET invoice_type = "' . $item['oldName'] . '"
                 WHERE invoice_type = "'. $item['newName'] .'";'
            );
        }
    }
}

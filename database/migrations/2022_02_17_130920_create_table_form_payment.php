<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTableFormPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_payment', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('code_cnab');
            $table->string('bank_code');
        });

        DB::table('form_payment')->insert([
            [
                'title'     => 'Conta Corrente - Outro titular',
                'code_cnab' => '01',
                'bank_code' => '341',
            ],
            [
                'title'     => 'Conta Corrente - Mesmo Titular',
                'code_cnab' => '06',
                'bank_code' => '341',
            ],
            [
                'title'     => 'Conta Poupança',
                'code_cnab' => '05',
                'bank_code' => '341',
            ],
            [
                'title'     => 'Cheque Pagamento',
                'code_cnab' => '02',
                'bank_code' => '341',
            ],
            [
                'title'     => 'DOC C - Outro Titular',
                'code_cnab' => '03',
                'bank_code' => '341',
            ],
            [
                'title'     => 'DOC D - Mesmo Titular',
                'code_cnab' => '07',
                'bank_code' => '341',
            ],
            [
                'title'     => 'OP - Ordem de Pagamento',
                'code_cnab' => '10',
                'bank_code' => '341',
            ],
            [
                'title'     => 'Boleto do Itaú',
                'code_cnab' => '30',
                'bank_code' => '341',
            ],
            [
                'title'     => 'Contas de Concessionárias',
                'code_cnab' => '13',
                'bank_code' => '341',
            ],
            [
                'title'     => 'Boleto de Outros Bancos',
                'code_cnab' => '11',
                'bank_code' => '341',
            ],
            [
                'title'     => 'Nota Fiscal – Liquidação Eletrônica',
                'code_cnab' => '32',
                'bank_code' => '341',
            ],
            [
                'title'     => 'TED – Outro Titular',
                'code_cnab' => '41',
                'bank_code' => '341',
            ],
            [
                'title'     => 'TED – Mesmo Titular',
                'code_cnab' => '43',
                'bank_code' => '341',
            ],
            [
                'title'     => 'PIX Transferência',
                'code_cnab' => '45',
                'bank_code' => '341',
            ],
            [
                'title'     => 'PIX QR-CODE',
                'code_cnab' => '47',
                'bank_code' => '341',
            ],
            [
                'title'     => 'Conta Corrente',
                'code_cnab' => '01',
                'bank_code' => '001',
            ],
            [
                'title'     => 'DOC/TED',
                'code_cnab' => '03',
                'bank_code' => '001',
            ],
            [
                'title'     => 'Poupança',
                'code_cnab' => '05',
                'bank_code' => '001',
            ],
            [
                'title'     => 'TED Outra Titularidade',
                'code_cnab' => '41',
                'bank_code' => '001',
            ],
            [
                'title'     => 'TED Mesma Titularidade',
                'code_cnab' => '43',
                'bank_code' => '001',
            ],
            [
                'title'     => 'Pix Transferência',
                'code_cnab' => '45',
                'bank_code' => '001',
            ],
            [
                'title'     => 'Pix QR­Code',
                'code_cnab' => '47',
                'bank_code' => '001',
            ],
            [
                'title'     => 'Depósito Judicial em Conta Corrente',
                'code_cnab' => '71',
                'bank_code' => '001',
            ]
        ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_payment');
    }
}

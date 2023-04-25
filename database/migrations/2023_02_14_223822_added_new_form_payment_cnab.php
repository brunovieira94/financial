<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedNewFormPaymentCnab extends Migration
{
    public function up()
    {
        Schema::table('form_payment', function (Blueprint $table) {
            $table->boolean('concessionaire_billet')->default(false);
        });

        DB::table('form_payment')->insert([
            [
                'title'     => 'Pagamento de Contas e Tributos com Código de Barras',
                'code_cnab' => '11',
                'group_form_payment_id' => 1,
                'bank_code' => '033',
                'same_ownership' => false,
                'concessionaire_billet' => true
            ],
            [
                'title'     => 'Com código de barras',
                'code_cnab' => '11',
                'group_form_payment_id' => 1,
                'bank_code' => '001',
                'same_ownership' => false,
                'concessionaire_billet' => true
            ]
        ]);

        DB::table('form_payment')->where('code_cnab', '13')->where('bank_code', '341')->update([
            'group_form_payment_id' => 1,
            'concessionaire_billet' => true,
        ]);
    }
}

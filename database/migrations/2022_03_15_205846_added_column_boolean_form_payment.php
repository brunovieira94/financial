<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedColumnBooleanFormPayment extends Migration
{
    public function up()
    {
        Schema::table('form_payment', function (Blueprint $table) {
            $table->boolean('same_ownership')->nullable();
        });

        DB::table('form_payment')
            ->where(['title' => 'Boleto'])
            ->update([
                'title' => 'Boleto Mesma Titularidade',
                'same_ownership' => true,
                'code_cnab' => '30',
            ]);

        DB::table('form_payment')
            ->insert([
                'title' => 'Boleto Outra Titularidade',
                'same_ownership' => false,
                'group_form_payment_id' => 1,
                'code_cnab' => '31',
                'bank_code' => '001'
            ]);

        DB::table('form_payment')
            ->where(['title' => 'Boleto do Itaú'])
            ->update([
                'same_ownership' => true,
            ]);

        DB::table('form_payment')
            ->where(['title' => 'Boleto de Outros Bancos'])
            ->update([
                'same_ownership' => false,
            ]);
    }

    public function down()
    {
        Schema::table('form_payment', function (Blueprint $table) {
            $table->dropColumn('same_ownership');
        });
    }
}

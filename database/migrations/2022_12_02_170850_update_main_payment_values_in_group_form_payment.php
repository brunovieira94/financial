<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMainPaymentValuesInGroupFormPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('group_form_payment')->whereIn('title', ['Boleto', 'PIX', 'DOC', 'TED'])->update(['main_payment' => 1]);
        DB::table('group_form_payment')->whereIn('title', ['DÉBITO EM CONTA', 'CHAVE PIX'])->update(['main_payment' => 0]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPixKeyToGroupFormPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('group_form_payment')->insert([
            'title' => 'CHAVE PIX',
            'main_payment' => '0',
        ]);
    }
}

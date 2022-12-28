<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateValueInGroupFormPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('group_form_payment')->where('title', '=', 'PIX SEM CNAB')->update(['title' => 'CHAVE PIX']);
    }
}

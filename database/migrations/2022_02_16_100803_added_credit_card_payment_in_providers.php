<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedCreditCardPaymentInProviders extends Migration
{
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->boolean('credit_card_payment')->default(false);
        });
    }

    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('credit_card_payment');
        });
    }
}


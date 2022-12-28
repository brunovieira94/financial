<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtherPaymentHasExchangeRates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('other_payment_has_exchange_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('other_payment_id')->unsigned();
            $table->foreign('other_payment_id')->references('id')->on('other_payments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('currency_id')->unsigned();
            $table->foreign('currency_id')->references('id')->on('currency')->cascadeOnDelete()->cascadeOnUpdate();
            $table->double('exchange_rate');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('other_payment_has_exchange_rates');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtherPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('other_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_form_payment_id')->unsigned();
            $table->foreign('group_form_payment_id')->references('id')->on('group_form_payment')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('bank_account_company_id')->unsigned()->nullable();
            $table->foreign('bank_account_company_id')->references('id')->on('bank_accounts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('payment_date')->nullable();
            $table->longText('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('other_payments');
    }
}

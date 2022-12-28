<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentRequestInstallmentsHaveOtherPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_request_installments_have_other_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('payment_request_installment_id')->unsigned();
            $table->foreign('payment_request_installment_id', 'fk_payment_request_installment_has_other_payment')->references('id')->on('payment_requests_installments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('other_payment_id')->unsigned();
            $table->foreign('other_payment_id', 'fk_other_payment_has_payment_request_installment')->references('id')->on('other_payments')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_request_installments_have_other_payments');
    }
}

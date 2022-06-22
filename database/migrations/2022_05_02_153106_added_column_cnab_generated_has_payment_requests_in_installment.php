<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnCnabGeneratedHasPaymentRequestsInInstallment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cnab_payment_requests_has_installments', function (Blueprint $table) {
            $table->integer('cnab_generated_has_payment_requests_id')->unsigned()->nullable();
            $table->foreign('cnab_generated_has_payment_requests_id', 'cnb_pr_id_foreign')->references('id')->on('cnab_generated_has_payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }
}

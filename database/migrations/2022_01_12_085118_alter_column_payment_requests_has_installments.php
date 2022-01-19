<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnPaymentRequestsHasInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->renameColumn('id_bill_to_pay', 'payment_request_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->renameColumn('payment_request_id', 'id_bill_to_pay');
        });
    }
}

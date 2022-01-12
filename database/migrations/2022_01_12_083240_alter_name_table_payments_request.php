<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNameTablePaymentsRequest extends Migration
{
    public function up()
    {
        Schema::rename('bills_to_pay', 'payment_requests');
        Schema::rename('bill_to_pay_has_tax', 'payment_requests_has_tax');
        Schema::rename('bill_to_pay_has_installments', 'payment_requests_installments');

    }

    public function down()
    {
        Schema::rename('payment_requests', 'bills_to_pay');
        Schema::rename('payment_requests_has_tax', 'bill_to_pay_has_tax');
        Schema::rename('payment_requests_installments', 'bill_to_pay_has_installments');
    }
}

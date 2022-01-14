<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnPaymentRequestInvoiceType extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('invoice_type')->nullable();
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
        });
    }
}

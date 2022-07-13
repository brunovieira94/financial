<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnsIntegrationPurchaseOrderPaymentRequest extends Migration
{
    public function up()
    {
        Schema::table('purchase_order_has_installments', function (Blueprint $table) {
            $table->double('amount_paid')->default(0);
        });
        Schema::table('payment_request_has_purchase_order_installments', function (Blueprint $table) {
            $table->double('amount_received')->default(0);
        });
    }

    public function down()
    {
        Schema::table('purchase_order_has_installments', function (Blueprint $table) {
            $table->dropColumn('amount_paid');
        });
        Schema::table('payment_request_has_purchase_order_installments', function (Blueprint $table) {
            $table->dropColumn('amount_received');
        });
    }
}

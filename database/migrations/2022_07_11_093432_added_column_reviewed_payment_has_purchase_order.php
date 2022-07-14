<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnReviewedPaymentHasPurchaseOrder extends Migration
{
    public function up()
    {
        Schema::table('payment_request_has_purchase_orders', function (Blueprint $table) {
            $table->boolean('reviewed')->default(false);
        });
    }

    public function down()
    {
        Schema::table('payment_request_has_purchase_orders', function (Blueprint $table) {
            $table->dropColumn('reviewed');
        });
    }
}

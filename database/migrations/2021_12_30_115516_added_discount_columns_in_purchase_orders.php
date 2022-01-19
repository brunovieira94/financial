<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedDiscountColumnsInPurchaseOrders extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->double('percentage_discount_services')->nullable();
            $table->double('money_discount_services')->nullable();
            $table->double('percentage_discount_products')->nullable();
            $table->double('money_discount_products')->nullable();
        });
    }

    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('percentage_discount_services')->nullable();
            $table->dropColumn('money_discount_services')->nullable();
            $table->dropColumn('percentage_discount_products')->nullable();
            $table->dropColumn('money_discount_products')->nullable();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedDiscountColumnsInPurchaseOrdersHasServices extends Migration
{
    public function up()
    {
        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->double('percentage_discount')->nullable();
            $table->double('money_discount')->nullable();
        });
    }

    public function down()
    {
        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->dropColumn('percentage_discount')->nullable();
            $table->dropColumn('money_discount')->nullable();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedUniqueDiscountInPurchaseOrdersHasProducts extends Migration
{
    public function up()
    {
        Schema::table('purchase_order_has_products', function (Blueprint $table) {
            $table->boolean('unique_discount');
        });
    }

    public function down()
    {
        Schema::table('purchase_order_has_products', function (Blueprint $table) {
            $table->dropColumn('unique_discount');
        });
    }
}

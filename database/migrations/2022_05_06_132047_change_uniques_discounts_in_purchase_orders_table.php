<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUniquesDiscountsInPurchaseOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->text('unique_discount')->default(0)->nullable()->change();
            $table->text('unique_product_discount')->default(0)->nullable()->change();
        });
    }
}

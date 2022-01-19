<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderHasProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_has_products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('purchase_order_id')->unsigned();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->double('unitary_value')->nullable();
            $table->integer('quantity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_order_has_products');
    }
}

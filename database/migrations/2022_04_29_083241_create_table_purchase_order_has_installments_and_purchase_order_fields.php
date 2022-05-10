<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTablePurchaseOrderHasInstallmentsAndPurchaseOrderFields extends Migration
{
    public function up()
    {
        Schema::create('purchase_order_has_installments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('purchase_order_id')->unsigned();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('parcel_number');
            $table->double('portion_amount');
            $table->date('due_date');
            $table->longText('note')->nullable();
            $table->double('percentage_discount')->nullable();
            $table->double('money_discount')->nullable();
            $table->boolean('invoice_received')->default(false);
            $table->boolean('invoice_paid')->default(false);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->integer('frequency_of_installments');
            $table->integer('installments_quantity');
            $table->boolean('unique_discount');
        });

        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->dropColumn('frequency_of_installments');
            $table->dropColumn('installments_quantity');
            $table->dropColumn('unique_discount');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order_has_installments');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('frequency_of_installments');
            $table->dropColumn('installments_quantity');
            $table->dropColumn('unique_discount');
        });

        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->integer('frequency_of_installments');
            $table->integer('installments_quantity');
            $table->boolean('unique_discount');
        });
    }
}

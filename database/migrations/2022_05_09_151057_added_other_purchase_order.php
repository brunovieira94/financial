<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedOtherPurchaseOrder extends Migration
{
    public function up()
    {
        Schema::create('payment_request_has_purchase_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('payment_request_id')->unsigned();
            $table->foreign('payment_request_id', 'pr_po_id_foreign')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('purchase_order_id')->unsigned();
            $table->foreign('purchase_order_id', 'po_pr_id_foreign')->references('id')->on('purchase_orders')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('payment_request_has_purchase_order_installments', function (Blueprint $table) {
            $table->integer('payment_request_has_purchase_order_id')->unsigned();
            $table->foreign('payment_request_has_purchase_order_id', 'fk_pr_po_id_foreign')->references('id')->on('payment_request_has_purchase_orders')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->integer('purchase_order_id')->unsigned()->nullable();
            $table->foreign('purchase_order_id', 'po_id_foreign')->references('id')->on('purchase_orders')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::dropIfExists('payment_request_has_purchase_orders');

        Schema::table('payment_request_has_purchase_order_installments', function (Blueprint $table) {
            $table->dropForeign('fk_pr_po_id_foreign');
            $table->dropColumn('payment_request_has_purchase_order_id');
        });
    }
}

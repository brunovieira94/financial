<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedPuchaseOrderInPaymentRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->integer('purchase_order_id')->unsigned()->nullable();
            $table->foreign('purchase_order_id', 'po_id_foreign')->references('id')->on('purchase_orders')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->boolean('allows_registration_without_purchase_order')->default(false);
        });

        Schema::table('purchase_order_has_installments', function (Blueprint $table) {
            $table->integer('payment_request_id')->unsigned()->nullable();
            $table->foreign('payment_request_id', 'prs_id_foreign')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create('payment_request_has_purchase_order_installments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('payment_request_id')->unsigned();
            $table->foreign('payment_request_id', 'pr_inst_id_foreign')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('purchase_order_has_installments_id')->unsigned();
            $table->foreign('purchase_order_has_installments_id', 'po_inst_id_foreign')->references('id')->on('purchase_order_has_installments')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign('po_id_foreign');
            $table->dropColumn('purchase_order_id');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('allows_registration_without_purchase_order');
        });

        Schema::table('purchase_order_has_installments', function (Blueprint $table) {
            $table->dropForeign('prs_id_foreign');
            $table->dropColumn('payment_request_id');
        });

        Schema::dropIfExists('payment_request_has_purchase_order_installments');
    }
}

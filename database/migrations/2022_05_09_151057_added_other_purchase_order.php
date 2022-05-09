<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedOtherPurchaseOrder extends Migration
{
    public function up()
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->integer('purchase_order_id')->unsigned()->nullable();
            $table->foreign('purchase_order_id', 'po_id_foreign')->references('id')->on('purchase_orders')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

    }

    public function down()
    {
        Schema::dropIfExists('payment_requests');
    }
}

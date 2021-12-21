<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplyApprovalFlows extends Migration
{
    public function up()
    {
        Schema::create('supply_approval_flows', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_purchase_order')->unsigned();
            $table->foreign('id_purchase_order')->references('id')->on('purchase_orders')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('order');
            $table->integer('status');
            $table->longText('reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supply_approval_flows');
    }
}

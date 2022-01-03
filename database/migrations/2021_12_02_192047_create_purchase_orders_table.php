<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('order_type');
            $table->integer('provider_id')->unsigned();
            $table->foreign('provider_id')->references('id')->on('providers')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('currency_id')->unsigned();
            $table->foreign('currency_id')->references('id')->on('currency')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->double('exchange_rate');
            $table->double('initial_total_value');
            $table->double('negotiated_total_value');
            $table->date('billing_date')->nullable();
            $table->integer('payment_condition')->nullable();
            $table->text('observations');
            $table->timestamps();
            $table->softDeletes();
            $table->double('increase_tolerance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
}

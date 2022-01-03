<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderServicesHasInstallmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_services_has_installments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('po_services_id')->unsigned();
            $table->foreign('po_services_id')->references('id')->on('purchase_order_has_services')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('parcel_number');
            $table->double('portion_amount');
            $table->date('due_date');
            $table->longText('note')->nullable();
            $table->double('percentage_discount')->nullable();
            $table->double('money_discount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_order_services_has_installments');
    }
}

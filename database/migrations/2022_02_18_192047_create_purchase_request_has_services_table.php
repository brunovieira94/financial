<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseRequestHasServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_request_has_services', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('purchase_request_id')->unsigned();
            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('service_id')->unsigned();
            $table->foreign('service_id')->references('id')->on('services')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('contract_duration');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_request_has_services');
    }
}

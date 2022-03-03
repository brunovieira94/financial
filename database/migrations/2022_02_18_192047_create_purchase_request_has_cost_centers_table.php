<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseRequestHasCostCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_request_has_cost_centers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('purchase_request_id')->unsigned();
            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('cost_center_id')->unsigned();
            $table->foreign('cost_center_id')->references('id')->on('cost_center')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->double('percentage')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_request_has_cost_centers');
    }
}

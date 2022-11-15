<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProviderQuotationHasServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provider_quotation_has_services', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('provider_quotation_id')->unsigned();
            $table->foreign('provider_quotation_id')->references('id')->on('provider_quotations')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('purchase_request_id')->unsigned();
            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('service_id')->unsigned();
            $table->foreign('service_id')->references('id')->on('services')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('contract_duration');
            $table->integer('quantity')->nullable();
            $table->double('unit_price')->nullable();
            $table->double('total_without_discount')->nullable();
            $table->double('discount')->nullable();
            $table->double('total_discount')->nullable();
            $table->longText('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('provider_quotation_has_services');
    }
}

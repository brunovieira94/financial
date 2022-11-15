<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProviderQuotationItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provider_quotation_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('provider_quotation_id')->unsigned();
            $table->foreign('provider_quotation_id')->references('id')->on('provider_quotations')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('provider_id')->unsigned();
            $table->foreign('provider_id')->references('id')->on('providers')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('provider_quotation_items');
    }
}

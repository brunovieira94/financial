<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('measurement_units_id')->unsigned()->nullable();
            $table->foreign('measurement_units_id')->references('id')->on('measurement_units')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('chart_of_accounts_id')->unsigned()->nullable();
            $table->foreign('chart_of_accounts_id')->references('id')->on('chart_of_accounts')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->text('description');
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
        Schema::dropIfExists('products');
    }
}

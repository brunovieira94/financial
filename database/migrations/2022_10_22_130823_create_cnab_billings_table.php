<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCnabBillingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cnab_billings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cnab_generated_id')->unsigned();
            $table->foreign('cnab_generated_id')->references('id')->on('cnab_generated')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('billing_payment_id')->unsigned();
            $table->foreign('billing_payment_id')->references('id')->on('billing_payments')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cnab_billings');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BillToPayHasTax extends Migration
{
    public function up()
    {
        Schema::create('bill_to_pay_has_tax', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_bill_to_pay')->unsigned();
            $table->foreign('id_bill_to_pay')->references('id')->on('bills_to_pay')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('id_type_of_tax')->unsigned();
            $table->foreign('id_type_of_tax')->references('id')->on('type_of_tax')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->double('tax_amount')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bill_to_pay_has_tax');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillToPayHasInstallmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bill_to_pay_has_installments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bill_to_pay')->unsigned();
            $table->foreign('bill_to_pay')->references('id')->on('bills_to_pay')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('parcel_number');
            $table->double('portion_amount');
            $table->date('due_date');
            $table->longText('note')->nullable();
            $table->boolean('pay');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bill_to_pay_has_installments');
    }
}

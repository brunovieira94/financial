<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillToPaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bills_to_pay', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_provider')->unsigned();
            $table->foreign('id_provider')->references('id')->on('providers')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->date('emission_date');
            $table->date('pay_date');
            $table->integer('id_bank_account_provider')->unsigned();
            $table->foreign('id_bank_account_provider')->references('id')->on('bank_accounts')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('id_bank_account_company')->unsigned();
            $table->foreign('id_bank_account_company')->references('id')->on('bank_accounts')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->double('amount');
            $table->integer('id_business')->unsigned();
            $table->foreign('id_business')->references('id')->on('business')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('id_cost_center')->unsigned();
            $table->foreign('id_cost_center')->references('id')->on('cost_center')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('id_chart_of_account')->unsigned();
            $table->foreign('id_chart_of_account')->references('id')->on('chart_of_accounts')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('id_currency')->references('id')->on('currency')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('id_currency')->unsigned();
            $table->double('exchange_rate');
            $table->integer('frequency_of_installments');
            $table->integer('id_user')->unsigned();
            $table->foreign('id_user')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            //NF
            $table->string('invoice_file')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('type_of_tax')->nullable();
            $table->double('tax_amount')->nullable();
            $table->double('net_value')->nullable();

            //Boleto
            $table->string('billet_file')->nullable();
            $table->string('bar_code')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bill_to_pays');
    }
}

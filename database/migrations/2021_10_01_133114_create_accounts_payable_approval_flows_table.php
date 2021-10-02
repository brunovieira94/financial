<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsPayableApprovalFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts_payable_approval_flows', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_bill_to_pay')->unsigned();
            $table->foreign('id_bill_to_pay')->references('id')->on('bills_to_pay')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('order');
            $table->integer('status');
            $table->longText('reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts_payable_approval_flows');
    }
}

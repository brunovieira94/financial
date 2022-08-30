<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserHasPaymentRequest extends Migration
{
    public function up()
    {
        Schema::create('user_has_payment_request', function (Blueprint $table) {
            $table->increments('id');
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('payment_request_id')->unsigned()->nullable();
            $table->integer('status')->nullable();
        });

        Schema::table('accounts_payable_approval_flows', function (Blueprint $table) {
            $table->integer('action')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_has_payment_request');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableAccountsPayableApprovalFlowLog extends Migration
{
    public function up()
    {
        Schema::create('accounts_payable_approval_flows_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->nullable();
            $table->string('motive')->nullable();
            $table->string('description')->nullable();
            $table->string('stage')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->integer('payment_request_id')->unsigned()->nullable();
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('recipient')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounts_payable_approval_flows_log');
    }
}

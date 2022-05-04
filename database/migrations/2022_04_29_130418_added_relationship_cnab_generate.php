<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedRelationshipCnabGenerate extends Migration
{
    public function up()
    {
        Schema::create('cnab_generated', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->dateTime('file_date');
            $table->string('file_name');
            $table->integer('status')->nullable();
        });

        Schema::create('cnab_generated_has_payment_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cnab_generated_id')->unsigned();
            $table->foreign('cnab_generated_id')->references('id')->on('cnab_generated')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('payment_request_id')->unsigned();
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create('cnab_payment_requests_has_installments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cnab_generated_id')->unsigned();
            $table->foreign('cnab_generated_id')->references('id')->on('cnab_generated')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('payment_request_id')->unsigned();
            $table->foreign('payment_request_id', 'pr_id_foreign')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('installment_id')->unsigned();
            $table->foreign('installment_id', 'inst_id_foreign')->references('id')->on('payment_requests_installments')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cnab_payment_requests_has_installments');
        Schema::dropIfExists('cnab_generated_has_payment_requests');
        Schema::dropIfExists('cnab_generated');
    }
}

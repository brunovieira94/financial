<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnLogUpload extends Migration
{
    public function up()
    {
        Schema::create('temporary_log_upload_payment_request', function (Blueprint $table) {
            $table->increments('id');
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('payment_request_id')->unsigned()->nullable();
            $table->text('error')->nullable();
            $table->string('folder')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('temporary_log_upload_payment_request');
    }
}

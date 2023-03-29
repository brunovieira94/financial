<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableLogDownloadArchives extends Migration
{
    public function up()
    {
        Schema::create('attachment_download_log', function (Blueprint $table) {
            $table->increments('id');
            $table->text('archive')->nullable();
            $table->integer('payment_request_id')->unsigned()->nullable();
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->text('error')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attachment_download_log');
    }
}

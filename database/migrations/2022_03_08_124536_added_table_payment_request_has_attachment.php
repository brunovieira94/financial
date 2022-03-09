<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedTablePaymentRequestHasAttachment extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('other_files');
        });
        Schema::create('payment_request_has_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('payment_request_id')->unsigned();
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('attachment')->nullable();
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('other_files');
        });
        Schema::dropIfExists('payment_request_has_attachments');
    }
}

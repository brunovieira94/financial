<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtherPaymentHasAttachments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('other_payment_has_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('other_payment_id')->unsigned();
            $table->foreign('other_payment_id')->references('id')->on('other_payments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('attachment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('other_payment_has_attachments');
    }
}

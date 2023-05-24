<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedAttachmentProviderIsBilling extends Migration
{
    public function up()
    {
        Schema::create('billing_has_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('billing_id')->unsigned()->nullable();
            $table->foreign('billing_id')->references('id')->on('billing')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('attachment')->nullable();
        });

        Schema::create('provider_has_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('provider_id')->unsigned()->nullable();
            $table->foreign('provider_id')->references('id')->on('providers')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('attachment')->nullable();
        });
    }
}

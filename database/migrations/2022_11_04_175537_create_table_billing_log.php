<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableBillingLog extends Migration
{
    public function up()
    {
        Schema::create('billing_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->nullable();
            $table->string('motive')->nullable();
            $table->string('description')->nullable();
            $table->string('stage')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->integer('billing_id')->unsigned()->nullable();
            $table->foreign('billing_id')->references('id')->on('billing')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('billing_log');
    }
}

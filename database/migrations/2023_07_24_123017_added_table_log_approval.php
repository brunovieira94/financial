<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedTableLogApproval extends Migration
{
    public function up()
    {
        Schema::create('approval_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->json('request')->nullable();
            $table->json('real_approval')->nullable();
            $table->timestamps();
        });
    }
}

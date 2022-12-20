<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedRelationshipUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('logged_user_id')->unsigned()->nullable();
            $table->foreign('logged_user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->dateTime('return_date')->nullable();
        });

        Schema::create('additional_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('user_additional_id')->unsigned()->nullable();
            $table->foreign('user_additional_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }
}

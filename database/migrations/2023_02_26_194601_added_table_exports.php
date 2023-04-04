<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedTableExports extends Migration
{
    public function up()
    {
        Schema::create('export', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('status')->default(false);
            $table->text('link')->nullable();
            $table->text('path')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('export');
    }
}

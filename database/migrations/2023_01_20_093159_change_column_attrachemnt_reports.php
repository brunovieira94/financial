<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnAttrachemntReports extends Migration
{
    public function up()
    {
        Schema::table('attachment_reports', function (Blueprint $table) {
            $table->text('link')->change();
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('title');
        });
    }

    public function down()
    {
        Schema::table('attachment_reports', function (Blueprint $table) {
            $table->string('link')->change();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CheckColumnIsExistsReportsAttachment extends Migration
{
    public function up()
    {

        $columns = Schema::getColumnListing('attachment_reports');
        Schema::table('attachment_reports', function (Blueprint $table) use ($columns) {
            if (!in_array('link', $columns)) {
                $table->text('link')->nullable();
            }
            if (!in_array('path', $columns)) {
                $table->string('path')->nullable();
            }
            if (!in_array('mails', $columns)) {
                $table->text('mails')->nullable();
            }
            if (!in_array('to', $columns)) {
                $table->dateTime('to')->nullable();
            }
            if (!in_array('from', $columns)) {
                $table->dateTime('from')->nullable();
            }
            if (!in_array('status', $columns)) {
                $table->boolean('status')->default(0);
            }
            if (!in_array('error', $columns)) {
                $table->text('error')->nullable();
            }
            if (!in_array('title', $columns)) {
                $table->string('title')->default('');
            }
            if (!in_array('user_id', $columns)) {
                $table->integer('user_id')->unsigned()->nullable();
                $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            }
        });
    }
}

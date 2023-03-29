<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnInAttachmentReports extends Migration
{
    public function up()
    {
        Schema::table('attachment_reports', function (Blueprint $table) {
            $table->text('link')->change();
        });
    }

    public function down()
    {
        Schema::table('attachment_reports', function (Blueprint $table) {
            $table->string('link')->change();
        });
    }
}

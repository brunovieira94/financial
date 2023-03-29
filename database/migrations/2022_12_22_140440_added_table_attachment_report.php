<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedTableAttachmentReport extends Migration
{
    public function up()
    {
        Schema::create('attachment_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->string('link')->nullable();
            $table->string('path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attachment_reports');
    }
}

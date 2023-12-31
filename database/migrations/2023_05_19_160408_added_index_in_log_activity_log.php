<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedIndexInLogActivityLog extends Migration
{
    public function up()
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['log_name', 'subject_id']);
        });
    }

    public function down()
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['log_name', 'subject_id']);
        });
    }
}

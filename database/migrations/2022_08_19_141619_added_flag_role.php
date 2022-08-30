<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedFlagRole extends Migration
{
    public function up()
    {
        Schema::table('role', function (Blueprint $table) {
            $table->boolean('transfer_approval')->default(false);
        });
    }

    public function down()
    {
        Schema::table('role', function (Blueprint $table) {
            $table->dropColumn('transfer_approval');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueEmailUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->change();
        });
    }
}

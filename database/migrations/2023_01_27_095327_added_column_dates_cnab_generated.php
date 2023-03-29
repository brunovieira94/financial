<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnDatesCnabGenerated extends Migration
{
    public function up()
    {
        Schema::table('cnab_generated', function (Blueprint $table) {
            $table->string('header_date')->nullable();
            $table->string('header_time')->nullable();
            $table->text('archive_return')->nullable();
        });
    }

    public function down()
    {
        Schema::table('cnab_generated', function (Blueprint $table) {
            $table->dropColumn('header_date');
            $table->dropColumn('header_time');
            $table->dropColumn('archive_return');
        });
    }
}

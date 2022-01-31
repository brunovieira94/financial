<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueTitleInCountriesTable extends Migration
{
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropUnique(['title']);
        });
    }

    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->unique('title');
        });
    }
}

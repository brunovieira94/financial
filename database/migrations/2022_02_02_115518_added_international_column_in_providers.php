<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedInternationalColumnInProviders extends Migration
{
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->boolean('international')->default(false);
        });
    }

    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('international');
        });
    }
}

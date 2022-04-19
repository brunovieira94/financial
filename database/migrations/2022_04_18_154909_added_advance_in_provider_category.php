<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedAdvanceInProviderCategory extends Migration
{
    public function up()
    {

        Schema::table('provider_categories', function (Blueprint $table) {
            $table->boolean('advance')->default(false);
        });
    }

    public function down()
    {
        Schema::table('provider_categories', function (Blueprint $table) {
            $table->dropColumn('advance');
        });
    }
}

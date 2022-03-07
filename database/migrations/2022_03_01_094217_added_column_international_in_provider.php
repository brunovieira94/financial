<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnInternationalInProvider extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('providers', 'international'))
        {
            Schema::table('providers', function (Blueprint $table) {
                $table->boolean('international')->default(false);
            });
        }
    }

    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('international');
        });
    }
}

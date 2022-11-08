<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnInRole extends Migration
{
    public function up()
    {
        Schema::table('role', function (Blueprint $table) {
            $table->boolean('financial_analyst')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('role', function (Blueprint $table) {
            $table->dropColumn('financial_analyst');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableSizeTypeToPlutoTableState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pluto_table_state', function (Blueprint $table) {
            $table->integer('table_size_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pluto_table_state', function (Blueprint $table) {
            $table->dropColumn('table_size_type');
        });
    }
}

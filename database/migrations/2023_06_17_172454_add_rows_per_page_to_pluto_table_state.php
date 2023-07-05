<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRowsPerPageToPlutoTableState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pluto_table_state', function (Blueprint $table) {
            $table->unsignedInteger('rows_per_page')->nullable();
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
            $table->dropColumn('rows_per_page');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortAndSortAttributeColumnsToPlutoTableStateHasColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pluto_table_state_has_columns', function (Blueprint $table) {
            $table->text('sort')->nullable();
            $table->text('sort_attribute')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pluto_table_state_has_columns', function (Blueprint $table) {
            $table->dropColumn('sort_attribute');
            $table->dropColumn('sort');
        });
    }
}

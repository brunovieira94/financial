<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BillingSuggestionAdded extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()

    {
        Schema::table('billing', function (Blueprint $table) {
            $table->string('suggestion_reason')->nullable();
            $table->boolean('suggestion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('billing', function (Blueprint $table) {
            $table->dropColumn('suggestion_reason');
            $table->dropColumn('suggestion');
        });
    }
}

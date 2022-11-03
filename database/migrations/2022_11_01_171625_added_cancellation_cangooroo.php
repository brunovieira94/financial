<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedCancellationCangooroo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()

    {
        Schema::table('cangooroo', function (Blueprint $table) {
            $table->dateTime('cancellation_date');
            $table->dateTime('cancellation_policies_start_date')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cangooroo', function (Blueprint $table) {
            $table->dropColumn('cancellation_date');
            $table->string('cancellation_policies_start_date')->change();
        });
    }
}

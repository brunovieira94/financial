<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedIsVcnCangooroo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()

    {
        Schema::table('cangooroo', function (Blueprint $table) {
            $table->boolean('is_vcn')->nullable();
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
            $table->dropColumn('is_vcn');
        });
    }
}

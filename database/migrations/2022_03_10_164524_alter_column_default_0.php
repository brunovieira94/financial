<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnDefault0 extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('initial_value');
            $table->double('initial_value')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('initial_value');
        });
    }
}

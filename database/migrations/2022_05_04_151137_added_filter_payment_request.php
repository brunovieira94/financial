<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedFilterPaymentRequest extends Migration
{
    public function up()
    {
        Schema::table('role', function (Blueprint $table) {
            $table->boolean('filter_cost_center')->default(true);
        });
    }

    public function down()
    {
        Schema::table('role', function (Blueprint $table) {
            $table->dropColumn('filter_cost_center');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedPaymentConditionUtileHotel extends Migration
{
    public function up()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->boolean('payment_condition_utile')->default(false);
        });
    }

    public function down()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('payment_condition_utile');
        });
    }
}

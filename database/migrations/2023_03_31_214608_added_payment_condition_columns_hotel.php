<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedPaymentConditionColumnsHotel extends Migration
{
    public function up()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->integer('payment_condition_days')->default(0);
            $table->boolean('payment_condition_before')->default(false);
            $table->integer('payment_condition')->default(0);
        });
    }

    public function down()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('payment_condition_days');
            $table->dropColumn('payment_condition_before');
            $table->dropColumn('payment_condition');
        });
    }
}

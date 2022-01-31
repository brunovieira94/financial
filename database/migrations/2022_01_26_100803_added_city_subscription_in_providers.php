<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedCitySubscriptionInProviders extends Migration
{
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('city_subscription')->nullable();
            $table->boolean('accept_billet_payment')->default(false);
        });
    }

    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('city_subscription');
            $table->dropColumn('accept_billet_payment');
        });
    }
}


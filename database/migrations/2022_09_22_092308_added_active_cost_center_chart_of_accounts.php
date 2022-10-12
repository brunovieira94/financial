<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedActiveCostCenterChartOfAccounts extends Migration
{
    public function up()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->boolean('active')->default(true);
        });

        Schema::table('cost_center', function (Blueprint $table) {
            $table->boolean('active')->default(true);
        });
    }

    public function down()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('active');
        });

        Schema::table('cost_center', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
}

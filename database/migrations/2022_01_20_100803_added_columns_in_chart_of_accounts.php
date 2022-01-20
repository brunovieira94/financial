<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnsInChartOfAccounts extends Migration
{
    public function up()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->integer('group');
            $table->string('accounting_title')->nullable();
            $table->string('accounting_code')->nullable();
            $table->string('group_title')->nullable();
            $table->string('group_code')->nullable();
            $table->string('referential_title')->nullable();
            $table->string('referential_code')->nullable();
        });
    }

    public function down()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('group');
            $table->dropColumn('accounting_title');
            $table->dropColumn('accounting_code');
            $table->dropColumn('group_title');
            $table->dropColumn('group_code');
            $table->dropColumn('referential_title');
            $table->dropColumn('referential_code');
        });
    }
}


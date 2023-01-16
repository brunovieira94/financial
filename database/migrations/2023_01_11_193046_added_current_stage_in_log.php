<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedCurrentStageInLog extends Migration
{
    public function up()
    {
        Schema::table('accounts_payable_approval_flows_log', function (Blueprint $table) {
            $table->integer('current_stage')->nullable();
        });
    }

    public function down()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('international');
            $table->dropColumn('current_stage');
        });
    }
}

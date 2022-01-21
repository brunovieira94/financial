<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumnCharOfAccounts extends Migration
{
    public function up()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->renameColumn('accounting_title', 'managerial_title');
            $table->renameColumn('accounting_code', 'managerial_code');
        });
    }

    public function down()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->renameColumn('managerial_title', 'accounting_title');
            $table->renameColumn('managerial_code', 'accounting_code');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnBankAccountsCheckNumber extends Migration
{
    public function up()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('agency_check_number')->default('')->change();
            $table->string('account_check_number')->default('')->change();
        });
    }

    public function down()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->integer('agency_check_number')->nullable()->change();
            $table->integer('account_check_number')->nullable()->change();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedFlagProviderGenericIsHiddenBankAccount extends Migration
{
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->boolean('generic_provider')->default(false);
        });
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->boolean('hidden')->default(false);
        });
    }

    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('generic_provider');
        });
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('hidden');
        });
    }
}

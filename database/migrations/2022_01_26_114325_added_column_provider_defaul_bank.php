<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnProviderDefaulBank extends Migration
{
    public function up()
    {
        Schema::table('provider_has_bank_accounts', function (Blueprint $table) {
            $table->boolean('default_bank')->default(false);
        });
    }

    public function down()
    {
        Schema::table('provider_has_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('default_bank');
        });
    }
}

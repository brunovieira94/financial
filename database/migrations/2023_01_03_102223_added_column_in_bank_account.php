<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnInBankAccount extends Migration
{
    public function up()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->boolean('international')->default(false);
            $table->text('address')->nullable();
            $table->string('iban_code')->nullable();
        });
    }

    public function down()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('international');
            $table->dropColumn('address');
            $table->dropColumn('iban_code');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProviderHasBankAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('provider_has_bank_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('provider_id')->unsigned();
            $table->integer('bank_account_id')->unsigned();
            $table->foreign('provider_id')->references('id')->on('providers')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('provider_has_bank_accounts');
    }
}

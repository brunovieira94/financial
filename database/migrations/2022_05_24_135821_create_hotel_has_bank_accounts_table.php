<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelHasBankAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('hotel_has_bank_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('default_bank')->default(false);
            $table->integer('hotel_id')->unsigned();
            $table->integer('bank_account_id')->unsigned();
            $table->foreign('hotel_id')->references('id')->on('hotels')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotel_has_bank_accounts');
    }
}

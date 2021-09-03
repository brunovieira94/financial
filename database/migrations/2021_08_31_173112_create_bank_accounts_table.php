<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
<<<<<<< HEAD
            $table->increments('id');
            $table->integer('agency_number');
            $table->integer('agency_check_number');
            $table->integer('account_number');
            $table->integer('account_check_number');
            $table->integer('bank_id')->unsigned();
            $table->foreign('bank_id')->references('id')->on('banks');
            $table->timestamps();
            $table->softDeletes();
=======
            $table->id();
            $table->timestamps();
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
        });
    }

    /**
<<<<<<< HEAD
     *
=======
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bank_accounts');
    }
}

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
            $table->increments('id');
            $table->string('agency_number')->nullable();
            $table->integer('agency_check_number')->nullable();
            $table->string('account_number')->nullable();
            $table->integer('account_check_number')->nullable();
            $table->string('pix_key')->nullable();
            $table->integer('pix_key_type')->nullable();
            $table->integer('account_type')->nullable();
            $table->integer('bank_id')->unsigned()->nullable();
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank_accounts');
    }
}

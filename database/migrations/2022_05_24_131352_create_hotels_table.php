<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelsTable extends Migration
{
    public function up()
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('id_hotel_cangooroo')->nullable();
            $table->string('id_hotel_omnibees')->nullable();
            $table->string('hotel_name')->nullable();
            $table->string('chain')->nullable();
            $table->string('email')->nullable();
            $table->string('email_omnibees')->nullable();
            $table->string('phone')->nullable();
            $table->integer('billing_type')->nullable(); //
            $table->integer('bank_account_id')->unsigned()->nullable();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('payment_type_id')->unsigned()->nullable();
            $table->foreign('payment_type_id')->references('id')->on('payment_types')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('holder_full_name')->nullable();
            $table->string('cpf_cnpj')->nullable();
            $table->boolean('is_valid')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotels');
    }
}

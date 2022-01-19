<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_name');
            $table->string('trade_name');
            $table->string('cnpj');
            $table->string('cep')->nullable();
            $table->integer('cities_id')->unsigned()->nullable();
            $table->foreign('cities_id')->references('id')->on('cities')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('address')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('district')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProvidersTable extends Migration
{
    public function up()
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_name');
            $table->string('trade_name')->nullable();
            $table->string('cnpj')->nullable();
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('responsible')->nullable();
            $table->string('responsible_phone')->nullable();
            $table->string('responsible_email')->nullable();
            $table->string('state_subscription')->nullable();
            $table->integer('chart_of_accounts_id')->unsigned()->nullable();
            $table->foreign('chart_of_accounts_id')->references('id')->on('chart_of_accounts')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('provider_categories_id')->unsigned();
            $table->foreign('provider_categories_id')->references('id')->on('provider_categories')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('cost_center_id')->unsigned()->nullable();
            $table->foreign('cost_center_id')->references('id')->on('cost_center')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('cep')->nullable();
            $table->integer('cities_id')->unsigned()->nullable();
            $table->foreign('cities_id')->references('id')->on('cities')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('address')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('district')->nullable();
            $table->json('phones')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('providers');
    }
}

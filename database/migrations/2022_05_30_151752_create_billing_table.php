<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingTable extends Migration
{
    public function up()
    {
        Schema::create('billing', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reserve')->nullable();
            $table->string('partner_value')->nullable();
            $table->dateTime('payDate')->nullable();
            $table->string('boleto_value')->nullable();
            $table->string('boleto_code')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('remark')->nullable();
            $table->string('oracle_protocol')->nullable();
            $table->string('cnpj')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('billing');
    }
}

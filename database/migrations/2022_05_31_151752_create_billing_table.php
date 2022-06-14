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
            $table->integer('cangooroo_booking_id')->nullable();
            $table->foreign('cangooroo_booking_id')->references('booking_id')->on('cangooroo')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('supplier_value')->nullable();
            $table->dateTime('pay_date')->nullable();
            $table->string('boleto_value')->nullable();
            $table->string('boleto_code')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('remark')->nullable();
            $table->string('oracle_protocol')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('payment_status')->nullable();
            $table->string('status_123')->nullable();
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

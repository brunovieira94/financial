<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnCurrencyOldPaymentRequest extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->double('amount_old');
            $table->integer('currency_old_id')->unsigned()->nullable();
            $table->foreign('currency_old_id')->references('id')->on('currency')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->double('net_value_old');
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('amount_old');
            $table->dropColumn('currency_old_id');
            $table->dropColumn('net_value_old');
        });
    }
}

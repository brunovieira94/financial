<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnsPaymentRequest extends Migration
{
    public function up()
    {

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->double('fees')->default(0);
            $table->double('discount')->default(0);
            $table->longText('note')->nullable();
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('fees');
            $table->dropColumn('discount');
            $table->dropColumn('note');
        });
    }
}

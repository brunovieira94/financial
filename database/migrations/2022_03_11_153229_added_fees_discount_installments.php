<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedFeesDiscountInstallments extends Migration
{
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->double('fees')->default(0);
            $table->double('discount')->default(0);
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('fees');
            $table->dropColumn('discount');
        });
    }

    public function down()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->dropColumn('fees');
            $table->dropColumn('discount');
        });
    }
}

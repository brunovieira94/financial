<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedPercentageDiscountInPaymentRequest extends Migration
{
    public function up()
    {

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->double('percentage_discount')->default(0);
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('percentage_discount');
        });
    }
}

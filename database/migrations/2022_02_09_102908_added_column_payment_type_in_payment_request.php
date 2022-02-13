<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedColumnPaymentTypeInPaymentRequest extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->integer('payment_type')->nullable();
        });

        DB::update('UPDATE payment_requests SET payment_type = 2 WHERE bar_code IS NULL AND invoice_number IS NULL;');
        DB::update('UPDATE payment_requests SET payment_type = 1 WHERE bar_code IS NOT NULL AND invoice_number IS NULL;');
        DB::update('UPDATE payment_requests SET payment_type = 0 WHERE invoice_number IS NOT NULL AND bar_code IS NULL;');
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
}

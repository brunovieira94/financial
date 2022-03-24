<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterIdPaymentRequest extends Migration
{
    public function up()
    {
        DB::statement(
        'UPDATE payment_requests set id = id+1000'
        );
    }

    public function down()
    {
        DB::statement(
        'UPDATE payment_requests set id = id-1000'
        );
    }
}

<?php

use App\Models\PaymentRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedAutoIncrement extends Migration
{
    public function up()
    {
        $paymentRequest = DB::table('payment_requests')->find(DB::table('payment_requests')->max('id'));
        $nextID = $paymentRequest == null ? 1000 : $paymentRequest->id + 1;

        DB::statement(
            'ALTER TABLE payment_requests AUTO_INCREMENT = ' . $nextID
        );
    }
}

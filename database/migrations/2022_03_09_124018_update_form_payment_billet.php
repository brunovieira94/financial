<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateFormPaymentBillet extends Migration
{
    public function up()
    {
        DB::table('payment_requests')
              ->where('payment_type', 1)
              ->update(['group_form_payment_id' => 1]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SolveAccountStatus extends Migration
{
    public function up()
    {
        DB::table('accounts_payable_approval_flows')
            ->where('payment_request_id', 21073)
            ->update(['status' => 1]);
    }
}

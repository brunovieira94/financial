<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddedStartValueStatusInstallments extends Migration
{
    public function up()
    {
        DB::table('payment_requests_installments')
            ->whereNull('status')
            ->update(['status' => 0]);
    }
}

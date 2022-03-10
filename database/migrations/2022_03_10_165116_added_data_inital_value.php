<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedDataInitalValue extends Migration
{
    public function up()
    {
        DB::table('payment_requests')
              ->whereNotNull('initial_value')
              ->update(['initial_value' => 0]);
    }
}

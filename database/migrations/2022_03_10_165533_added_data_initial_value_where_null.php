<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class AddedDataInitialValueWhereNull extends Migration
{
    public function up()
    {
        DB::table('payment_requests')
              ->where(['initial_value' => NULL])
              ->update(['initial_value' => 0]);
    }
}

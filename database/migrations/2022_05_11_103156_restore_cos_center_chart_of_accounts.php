<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RestoreCosCenterChartOfAccounts extends Migration
{
    public function up()
    {
        DB::table('chart_of_accounts')
              ->update(['deleted_at' => NULL]);

        DB::table('cost_center')
              ->update(['deleted_at' => NULL]);
    }
}

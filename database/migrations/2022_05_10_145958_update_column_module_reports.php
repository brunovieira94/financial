<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateColumnModuleReports extends Migration
{
    public function up()
    {
        DB::table('module')
              ->where('route', 'payment-requests-cnab-generated')
              ->update(['route' => 'payment-requests-cnab-generated-list']);
    }
}

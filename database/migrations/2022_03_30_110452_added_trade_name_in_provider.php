<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedTradeNameInProvider extends Migration
{

    public function up()
    {
        DB::statement(
            'UPDATE providers
             SET trade_name = full_name
             WHERE trade_name IS NULL;'
        );
    }

}

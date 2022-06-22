<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SolveProblemColumnSameOwnership extends Migration
{
    public function up()
    {
        DB::statement(
            "UPDATE form_payment
             SET same_ownership = 1
             WHERE title like '%mesma%' OR title like '%mesmo%';"
        );
    }

}

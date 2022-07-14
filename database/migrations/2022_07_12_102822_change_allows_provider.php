<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeAllowsProvider extends Migration
{
    public function up()
    {
        DB::table('providers')
            ->update(['allows_registration_without_purchase_order' => true]);
    }
}

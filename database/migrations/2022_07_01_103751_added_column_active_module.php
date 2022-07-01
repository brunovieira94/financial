<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedColumnActiveModule extends Migration
{
    public function up()
    {
        Schema::table('module', function (Blueprint $table) {
            $table->boolean('active')->default(true);
        });

        DB::table('module')
            ->where('route', 'disapproved-payment-request')
            ->update(['active' => false]);
    }

    public function down()
    {
        Schema::table('module', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
}

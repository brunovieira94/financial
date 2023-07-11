<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnTestExport extends Migration
{
    public function up()
    {
        Schema::table('export', function (Blueprint $table) {
            $table->boolean('test')->default(false);
        });
    }
}

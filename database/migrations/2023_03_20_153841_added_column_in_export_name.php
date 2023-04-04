<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnInExportName extends Migration
{
    public function up()
    {
        Schema::table('export', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
    }
}

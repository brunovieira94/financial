<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnExtensionInExport extends Migration
{
    public function up()
    {
        Schema::table('export', function (Blueprint $table) {
            $table->string('extension')->nullable();
        });
    }
}

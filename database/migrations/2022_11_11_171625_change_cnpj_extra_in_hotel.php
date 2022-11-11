<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCnpjExtraInHotel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()

    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('cnpj_extra')->nullable()->change();
        });
    }

}

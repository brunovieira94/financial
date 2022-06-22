<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueIdHotelCangooroo extends Migration
{
    public function up()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('id_hotel_cangooroo')->nullable(false)->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropUnique('id_hotel_cangooroo')->nullable();
        });
    }
}

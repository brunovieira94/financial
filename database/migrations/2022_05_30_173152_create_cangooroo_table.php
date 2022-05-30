<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCangoorooTable extends Migration
{
    public function up()
    {
        Schema::create('cangooroo', function (Blueprint $table) {
            $table->string('booking_id');
            $table->array('guests');
            $table->string('service_id');
            $table->string('supplier_reservation_code');
            $table->string('status');
            $table->string('reservation_date');
            $table->string('check_in');
            $table->string('check_out');
            $table->int('number_of_nights');
            $table->string('supplier_hotel_id');
            $table->string('hotel_id');
            $table->string('hotel_name');
            $table->string('city_name');
            $table->string('agency_name');
            $table->string('creation_user');
            $table->float('selling_price');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cangooroo');
    }
}

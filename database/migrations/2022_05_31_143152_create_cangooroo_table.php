<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCangoorooTable extends Migration
{
    public function up()
    {
        Schema::create('cangooroo', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('booking_id')->unique();
            $table->string('guests')->nullable();
            $table->string('service_id')->nullable();
            $table->string('supplier_reservation_code')->nullable();
            $table->string('status')->nullable();
            $table->string('reservation_date')->nullable();
            $table->string('check_in')->nullable();
            $table->string('check_out')->nullable();
            $table->integer('number_of_nights')->nullable();
            $table->string('supplier_hotel_id')->nullable();
            $table->string('hotel_id')->nullable();
            $table->foreign('hotel_id')->references('id_hotel_cangooroo')->on('hotels')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('hotel_name')->nullable();
            $table->string('city_name')->nullable();
            $table->string('agency_name')->nullable();
            $table->string('creation_user')->nullable();
            $table->string('123_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('cancellation_policies_start_date')->nullable();
            $table->float('cancellation_policies_value')->nullable();
            $table->float('selling_price')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cangooroo');
    }
}

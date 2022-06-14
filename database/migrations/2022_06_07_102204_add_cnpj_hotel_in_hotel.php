<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCnpjHotelInHotel extends Migration
{
    public function up()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('cnpj_hotel')->nullable();
            $table->text('observations')->nullable();
            $table->integer('form_of_payment')->nullable();
            $table->dropForeign('hotels_group_form_payment_id_foreign');
            $table->dropColumn('group_form_payment_id');
        });
    }

    public function down()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('cnpj_hotel');
            $table->dropColumn('observations');
            $table->dropColumn('form_of_payment');
            $table->integer('group_form_payment_id')->unsigned()->nullable();
            $table->foreign('group_form_payment_id')->references('id')->on('group_form_payment')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }
}

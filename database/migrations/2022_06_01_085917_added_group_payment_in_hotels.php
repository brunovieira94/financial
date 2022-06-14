<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedGroupPaymentInHotels extends Migration
{
    public function up()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->integer('group_form_payment_id')->unsigned()->nullable();
            $table->foreign('group_form_payment_id')->references('id')->on('group_form_payment')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropForeign('hotels_payment_type_id_foreign');
            $table->dropColumn('payment_type_id');
        });
    }

    public function down()
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropForeign('payment_requests_group_form_payment_id_foreign');
            $table->dropColumn('group_form_payment_id');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->integer('payment_type_id')->unsigned()->nullable();
            $table->foreign('payment_type_id')->references('id')->on('payment_types')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }
}

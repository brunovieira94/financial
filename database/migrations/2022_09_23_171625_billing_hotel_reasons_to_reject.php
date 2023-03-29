<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BillingHotelReasonsToReject extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()

    {
        Schema::table('billing', function (Blueprint $table) {
            $table->integer('reason_to_reject_id')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('billing', function (Blueprint $table) {
            $table->dropForeign(['reason_to_reject_id']);
            $table->foreign('reason_to_reject_id')->references('id')->on('reasons_to_reject')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BillingRemoveUniqueBookingId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()

    {
        Schema::table('billing', function (Blueprint $table) {
            $table->dropForeign('billing_cangooroo_booking_id_foreign');
        });

        Schema::table('cangooroo', function (Blueprint $table) {
            $table->integer('service_id')->change();
            $table->dropUnique(['booking_id']);
            $table->unique(['service_id']);
        });

        Schema::table('billing', function (Blueprint $table) {
            $table->integer('cangooroo_service_id')->nullable();
            $table->foreign('cangooroo_service_id')->references('service_id')->on('cangooroo')->constrained()->onUpdate('cascade')->onDelete('cascade');
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
            $table->dropForeign('billing_cangooroo_service_id_foreign');
        });

        Schema::table('cangooroo', function (Blueprint $table) {
            $table->unique(['booking_id']);
            $table->dropUnique(['service_id']);
            $table->string('service_id')->change();
        });

        Schema::table('billing', function (Blueprint $table) {
            $table->foreign('cangooroo_booking_id')->references('booking_id')->on('cangooroo')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }
}

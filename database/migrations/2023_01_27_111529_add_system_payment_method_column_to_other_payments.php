<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSystemPaymentMethodColumnToOtherPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('other_payments', function (Blueprint $table) {
            $table->integer('system_payment_method')->nullable();
        });

        // Until now, all other payments where made by using the Graphical User Interface
        DB::table('other_payments')->whereNull('system_payment_method')->update([
            'system_payment_method' => 1, // constants.systemPaymentMethod.gui = 1
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('other_payments', function (Blueprint $table) {
            $table->dropColumn('system_payment_method');
        });
    }
}

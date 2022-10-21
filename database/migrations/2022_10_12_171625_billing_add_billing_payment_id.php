<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BillingAddBillingPaymentId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()

    {
        Schema::table('billing', function (Blueprint $table) {
            $table->integer('billing_payment_id')->unsigned()->nullable();
            $table->foreign('billing_payment_id')->references('id')->on('billing_payments')->constrained()->onUpdate('cascade')->onDelete('cascade');
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
            $table->dropForeign('billing_billing_payment_id_foreign');
            $table->dropColumn('billing_payment_id');
        });
    }
}

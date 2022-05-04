<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedInitialDateInPurchaseOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('initial_date')->nullable();
        });
        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->integer('quantity');
        });
    }

    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('initial_date');
        });
        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
}

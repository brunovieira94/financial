<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedFrequencyInPurchaseOrdersHasServices extends Migration
{
    public function up()
    {
        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->integer('frequency_of_installments');
            $table->integer('contract_duration');
        });
    }

    public function down()
    {
        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->dropColumn('frequency_of_installments');
            $table->dropColumn('contract_duration');
        });
    }
}

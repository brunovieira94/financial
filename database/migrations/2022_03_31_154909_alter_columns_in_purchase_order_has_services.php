<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnsInPurchaseOrderHasServices extends Migration
{
    public function up()
    {

        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->renameColumn('contract_duration', 'installments_quantity');
            $table->integer('contract_time');
            $table->integer('contract_frequency');
        });
    }

    public function down()
    {
        Schema::table('purchase_order_has_services', function (Blueprint $table) {
            $table->renameColumn('installments_quantity', 'contract_duration');
            $table->dropColumn('contract_time');
            $table->dropColumn('contract_frequency');
        });
    }
}

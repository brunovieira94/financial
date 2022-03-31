<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnsInPurchaseOrderServicesHasInstallments extends Migration
{
    public function up()
    {

        Schema::table('purchase_order_services_has_installments', function (Blueprint $table) {
            $table->boolean('invoice_received')->default(false);
            $table->boolean('invoice_paid')->default(false);
        });
    }

    public function down()
    {
        Schema::table('purchase_order_services_has_installments', function (Blueprint $table) {
            $table->dropColumn('invoice_received');
            $table->dropColumn('invoice_paid');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnsInPurchaseRequestHasServices extends Migration
{
    public function up()
    {
        Schema::table('purchase_request_has_services', function (Blueprint $table) {
            $table->text('observations');
            $table->integer('contract_type');
        });
    }

    public function down()
    {
        Schema::table('purchase_request_has_services', function (Blueprint $table) {
            $table->dropColumn('observations');
            $table->dropColumn('contract_type');
        });
    }
}

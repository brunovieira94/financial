<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnsInPurchaseRequestHasProducts extends Migration
{
    public function up()
    {
        Schema::table('purchase_request_has_products', function (Blueprint $table) {
            $table->text('observations');
        });
    }

    public function down()
    {
        Schema::table('purchase_request_has_products', function (Blueprint $table) {
            $table->dropColumn('observations');
        });
    }
}

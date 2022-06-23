<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedInOrderInPurchaseRequestHasProducts extends Migration
{
    public function up()
    {
        Schema::table('purchase_request_has_products', function (Blueprint $table) {
            $table->integer('in_order')->default(0);
        });
    }

    public function down()
    {
        Schema::table('purchase_request_has_products', function (Blueprint $table) {
            $table->dropColumn('in_order');
        });
    }
}

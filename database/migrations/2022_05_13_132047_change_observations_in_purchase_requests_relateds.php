<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeObservationsInPurchaseRequestsRelateds extends Migration
{
    public function up()
    {
        Schema::table('purchase_request_has_products', function (Blueprint $table) {
            $table->text('observations')->nullable()->change();
        });
        Schema::table('purchase_request_has_services', function (Blueprint $table) {
            $table->text('observations')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('purchase_request_has_products', function (Blueprint $table) {
            $table->text('observations')->nullable(false)->change();
        });
        Schema::table('purchase_request_has_services', function (Blueprint $table) {
            $table->text('observations')->nullable(false)->change();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveProviderInPurchaseRequest extends Migration
{
    public function up()
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->integer('status')->default(0);
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });
    }

    public function down()
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->integer('provider_id')->unsigned();
            $table->foreign('provider_id')->references('id')->on('providers')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }
}

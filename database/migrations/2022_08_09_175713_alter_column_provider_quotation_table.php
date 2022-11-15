<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnProviderQuotationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('provider_quotation_has_services', function (Blueprint $table) {
            $table->integer('provider_quotation_item_id')->unsigned()->nullable()->after('id');
            $table->foreign('provider_quotation_item_id', 'squotation_item_id')->references('id')->on('provider_quotation_items')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->dropForeign('provider_quotation_has_services_provider_quotation_id_foreign');
            $table->dropColumn('provider_quotation_id');
            $table->dropForeign('provider_quotation_has_services_purchase_request_id_foreign');
            $table->dropColumn('purchase_request_id');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
            $table->dropColumn('deleted_at');
        });

        Schema::table('provider_quotation_has_products', function (Blueprint $table) {
            $table->integer('provider_quotation_item_id')->unsigned()->nullable()->after('id');
            $table->foreign('provider_quotation_item_id', 'pquotation_item_id')->references('id')->on('provider_quotation_items')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->dropForeign('provider_quotation_has_products_provider_quotation_id_foreign');
            $table->dropColumn('provider_quotation_id');
            $table->dropForeign('provider_quotation_has_products_purchase_request_id_foreign');
            $table->dropColumn('purchase_request_id');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
            $table->dropColumn('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

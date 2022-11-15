<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SolveColumnProviderQuotationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('provider_quotation_items', function (Blueprint $table) {
            $table->dropColumn('selected_services');
            $table->dropColumn('selected_products');
        });

        Schema::table('provider_quotation_items', function (Blueprint $table) {
            $table->json('selected_services');
            $table->json('selected_products');
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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedPercentageProviderQuotationHasCostCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('provider_quotation_has_cost_centers', function (Blueprint $table) {
            $table->double('percentage')->nullable();
        });
    }
}

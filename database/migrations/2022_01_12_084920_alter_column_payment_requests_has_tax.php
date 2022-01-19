<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnPaymentRequestsHasTax extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_requests_has_tax', function (Blueprint $table) {
            $table->renameColumn('id_bill_to_pay', 'payment_request_id');
            $table->renameColumn('id_type_of_tax', 'type_of_tax_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_requests_has_tax', function (Blueprint $table) {
            $table->renameColumn('payment_request_id', 'id_bill_to_pay');
            $table->renameColumn('type_of_tax_id', 'id_type_of_tax');
        });
    }
}

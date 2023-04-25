<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToPaymentRequestInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->date('verification_period')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('revenue_code')->nullable();
            $table->string('tax_file_phone_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->dropColumn('verification_period');
            $table->dropColumn('reference_number');
            $table->dropColumn('revenue_code');
            $table->dropColumn('tax_file_phone_number');
        });
    }
}

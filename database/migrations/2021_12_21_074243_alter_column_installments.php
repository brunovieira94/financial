<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bill_to_pay_has_installments', function (Blueprint $table) {
            $table->string('status')->nullable();
            $table->string('codBank')->nullable();
            $table->double('amount_received')->nullable();
            $table->dropColumn('pay');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('codBank');
            $table->dropColumn('amount_received');
            $table->boolean('pay')->nullable();
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnInInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->string('client_identifier')->nullable();
            $table->string('client_name')->nullable();
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
            $table->dropColumn('client_identifier');
            $table->string('client_name');
        });
    }
}

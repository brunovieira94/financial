<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnPaymentRequesteHasInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->integer('status')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });
    }
}

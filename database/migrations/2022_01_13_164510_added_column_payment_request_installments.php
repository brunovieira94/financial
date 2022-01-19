<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedColumnPaymentRequestInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->date('extension_date')->nullable();
            $table->date('competence_date')->nullable();
        });
        DB::statement('UPDATE payment_requests_installments SET extension_date = due_date');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('extension_date');
            $table->dropColumn('competence_date');
        });
    }
}

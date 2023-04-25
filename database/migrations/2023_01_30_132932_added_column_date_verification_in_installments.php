<?php

use App\Models\PaymentRequestHasInstallmentsClean;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnDateVerificationInInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        PaymentRequestHasInstallmentsClean::whereNotNull('verification_period')->update([
            'verification_period' => null
        ]);

        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->json('verification_period')->change();
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
            $table->date('verification_period')->change();
        });
    }
}

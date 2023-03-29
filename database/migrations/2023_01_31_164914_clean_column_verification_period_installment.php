<?php

use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasInstallmentsClean;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CleanColumnVerificationPeriodInstallment extends Migration
{
    public function up()
    {
        PaymentRequestHasInstallmentsClean::whereNotNull('verification_period')->update([
            'verification_period' => null
        ]);

        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->json('verification_period')->change();
        });
    }

    public function down()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->date('verification_period')->change();
        });
    }
}

<?php

use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SolveChangesInstallments extends Migration
{
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->double('fine')->default(0);
            $table->string('billet_number')->nullable();
            $table->integer('group_form_payment_id')->unsigned()->nullable();
            $table->foreign('group_form_payment_id')->references('id')->on('group_form_payment')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('bank_account_provider_id')->unsigned()->nullable();
            $table->foreign('bank_account_provider_id')->references('id')->on('bank_accounts')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('bar_code')->nullable();
        });

        foreach (PaymentRequest::withoutGlobalScopes()->get() as $paymentRequest) {
            PaymentRequestHasInstallments::where('payment_request_id', $paymentRequest->id)
                ->update([
                    'group_form_payment_id' => $paymentRequest->group_form_payment_id,
                    'bank_account_provider_id' => $paymentRequest->bank_account_provider_id,
                    'bar_code' => $paymentRequest->bar_code,
                ]);
        }
    }

    public function down()
    {
        //
    }
}

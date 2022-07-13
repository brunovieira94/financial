<?php

use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedInstallmentInPaymentRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (PaymentRequest::withoutGlobalScopes()->get() as $paymentRequest) {
            if (!PaymentRequestHasInstallments::where('payment_request_id', $paymentRequest['id'])->exists()) {
                $portionAmount = 0;
                if ($paymentRequest['amount'] != null) {
                    $portionAmount = $paymentRequest['amount'];
                }
                if ($paymentRequest['net_value'] != null) {
                    $portionAmount = $paymentRequest['net_value'];
                }
                PaymentRequestHasInstallments::create([
                    'payment_request_id' => $paymentRequest['id'],
                    'parcel_number' => 1,
                    'portion_amount' => $portionAmount,
                    'due_date' => $paymentRequest['extension_date'] ?? $paymentRequest['emission_date'],
                    'status' => 0,
                    'extension_date' => $paymentRequest['extension_date'],
                    'competence_date' => $paymentRequest['extension_date'],
                    'fess' => 0,
                    'discount' => 0,
                    'initial_value' => $portionAmount,
                    'percentage_discount' => 0,
                    'fine' => 0,
                    'group_form_payment_id' => $paymentRequest['group_form_payment_id'],
                    'bar_code' => $paymentRequest['bar_code'],
                    'bank_account_provider_id' => $paymentRequest['bank_account_provider_id'],
                ]);
            }
        }
        foreach (PaymentRequest::withoutGlobalScopes()->where('deleted_at', '!=', null)->get() as $paymentRequest) {
            if (!PaymentRequestHasInstallments::where('payment_request_id', $paymentRequest['id'])->exists()) {
                $portionAmount = 0;
                if ($paymentRequest['amount'] != null) {
                    $portionAmount = $paymentRequest['amount'];
                }
                if ($paymentRequest['net_value'] != null) {
                    $portionAmount = $paymentRequest['net_value'];
                }
                PaymentRequestHasInstallments::create([
                    'payment_request_id' => $paymentRequest['id'],
                    'parcel_number' => 1,
                    'portion_amount' => $portionAmount,
                    'due_date' => $paymentRequest['extension_date'] ?? $paymentRequest['emission_date'],
                    'status' => 0,
                    'extension_date' => $paymentRequest['extension_date'],
                    'competence_date' => $paymentRequest['extension_date'],
                    'fess' => 0,
                    'discount' => 0,
                    'initial_value' => $portionAmount,
                    'percentage_discount' => 0,
                    'fine' => 0,
                    'group_form_payment_id' => $paymentRequest['group_form_payment_id'],
                    'bar_code' => $paymentRequest['bar_code'],
                    'bank_account_provider_id' => $paymentRequest['bank_account_provider_id'],
                ]);
            }
        }
    }
}

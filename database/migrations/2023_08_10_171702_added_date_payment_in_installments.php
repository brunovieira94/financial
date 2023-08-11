<?php

use App\Models\CnabGenerated;
use App\Models\CnabPaymentRequestsHasInstallments;
use App\Models\OtherPayment;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Models\PaymentRequestHasInstallmentsThatHaveOtherPayments;
use Illuminate\Database\Migrations\Migration;

class AddedDatePaymentInInstallments extends Migration
{
    public function up()
    {
        foreach (PaymentRequestHasInstallmentsClean::where('status', 4)->whereNull('payment_made_date')->get() as $installment) {

            $datePayment = null;
            if (CnabPaymentRequestsHasInstallments::where('installment_id', $installment->id)->exists()) {
                $cnabInstallments = CnabPaymentRequestsHasInstallments::where('installment_id', $installment->id)->orderBy('id', 'DESC')->first();
                $cnabGenerated = CnabGenerated::find($cnabInstallments->cnab_generated_id);
                $datePayment = explode(" ", $cnabGenerated->file_date)[0];
            } else if (PaymentRequestHasInstallmentsThatHaveOtherPayments::where('payment_request_installment_id', $installment->id)->exists()) {
                $paymentRequestHasInstallmentsThatHaveOtherPayments = PaymentRequestHasInstallmentsThatHaveOtherPayments::where('payment_request_installment_id', $installment->id)->orderBy('id', 'DESC')->first();
                $otherPayment = OtherPayment::find($paymentRequestHasInstallmentsThatHaveOtherPayments->other_payment_id);
                $datePayment = $otherPayment->payment_date;
            }

            if ($datePayment != null) {
                PaymentRequestHasInstallmentsClean::where('id', $installment->id)
                    ->update(['payment_made_date' => $datePayment]);
            }
        }
    }
}

<?php

use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasPurchaseOrderInstallments;
use App\Models\PurchaseOrderHasInstallments;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SolveProblemAmountPaidInstallmentPurchaseOrder extends Migration
{
    public function up()
    {
        foreach(PaymentRequest::withoutGlobalScopes()->where('deleted_at', '!=', null)->get() as $paymentRequest){
            foreach (PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $paymentRequest['id'])->get() as $paymentRequestHasPurchaseOrderInstallments) {
                if (PurchaseOrderHasInstallments::where('id', $paymentRequestHasPurchaseOrderInstallments['purchase_order_has_installments_id'])->exists()) {
                    $installmentPurchaseOrder = PurchaseOrderHasInstallments::findOrFail($paymentRequestHasPurchaseOrderInstallments['purchase_order_has_installments_id']);
                    $installmentPurchaseOrder->amount_paid -= $paymentRequestHasPurchaseOrderInstallments['amount_received'];
                    $installmentPurchaseOrder->save();
                }
            }
        }
    }
}

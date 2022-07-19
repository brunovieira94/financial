<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasPurchaseOrderInstallments;
use App\Models\PurchaseOrderHasInstallments;

class CheckStorePaymentRequestIntegration
{
    private $instalmentPurchaseOrder;

    public function __construct(PurchaseOrderHasInstallments $instalmentPurchaseOrder)
    {
        $this->instalmentPurchaseOrder = $instalmentPurchaseOrder;
    }

    public function handle(Request $request, Closure $next)
    {
        $paymentRequestInfo = $request->all();

        if (array_key_exists('installment_purchase_order', $paymentRequestInfo)) {
            $amountReceived = 0;
            foreach ($paymentRequestInfo['installment_purchase_order'] as $instalmentPurchaseOrder) {
                $amountReceived += $instalmentPurchaseOrder['amount_received'];
                $installmentPurchase = PurchaseOrderHasInstallments::FindOrFail($instalmentPurchaseOrder['installment']);
                if ($request->route('id') != null) {
                    if(PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $request->route('id'))->where('purchase_order_has_installments_id', $instalmentPurchaseOrder['installment'])->exists()){
                        $paymentRequestHasPurchaseOrderInstallments = PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $request->route('id'))->where('purchase_order_has_installments_id', $instalmentPurchaseOrder['installment'])->first();
                        $installmentPurchase->amount_paid -= $paymentRequestHasPurchaseOrderInstallments->amount_received;
                    }
                }
                $installmentPurchase->amount_paid += $instalmentPurchaseOrder['amount_received'];
                if ($installmentPurchase->amount_paid > ($installmentPurchase->portion_amount - $installmentPurchase->money_discount)) {
                    return response()->json([
                        'error' => 'A soma do valor recebido para a parcela: ' . $installmentPurchase->id . ' é maior do que necessário para o pagamento completo da mesma.'
                    ], 422);
                    break;
                }
            }

            if ($amountReceived != $paymentRequestInfo['amount']) {
                return response()->json([
                    'error' => 'A soma dos valores recebidos da parcela não confere com o valor total da solicitação de pagamento.'
                ], 422);
            }
        }
        return $next($request);
    }
}

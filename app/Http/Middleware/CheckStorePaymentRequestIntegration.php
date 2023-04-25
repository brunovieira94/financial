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
                $installmentPurchase = PurchaseOrderHasInstallments::with('purchase_order')->findOrFail($instalmentPurchaseOrder['installment']);
                if ($request->route('id') != null) {
                    if (PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $request->route('id'))->where('purchase_order_has_installments_id', $instalmentPurchaseOrder['installment'])->exists()) {
                        $paymentRequestHasPurchaseOrderInstallments = PaymentRequestHasPurchaseOrderInstallments::where('payment_request_id', $request->route('id'))->where('purchase_order_has_installments_id', $instalmentPurchaseOrder['installment'])->first();
                        $installmentPurchase->amount_paid -= $paymentRequestHasPurchaseOrderInstallments->amount_received;
                    }
                }

                $valueFinal = $installmentPurchase->portion_amount - $installmentPurchase->money_discount;
                $amount = ((($valueFinal * $installmentPurchase->purchase_order->increase_tolerance) / 100) + $valueFinal);
                $installmentPurchase->amount_paid += $instalmentPurchaseOrder['amount_received'];

                if ($installmentPurchase->amount_paid > $amount) {
                    return response()->json([
                        'error' => 'A soma do valor recebido para a parcela: ' . $installmentPurchase->id . ' é maior do que necessário para o pagamento completo da mesma, verifique o desconto aplicado e o valor informado.'
                    ], 422);
                    break;
                }
            }

            //solve error float value PHP
            $value1 = floatval($amountReceived);
            if(array_key_exists('exchange_rate', $paymentRequestInfo) && floatval(str_replace(',', '.', $paymentRequestInfo['exchange_rate'])) > 0) {
                $paymentRequestInfo['amount'] = $paymentRequestInfo['amount'] / floatval(str_replace(',', '.', $paymentRequestInfo['exchange_rate']));
            }
            $value2 = floatval($paymentRequestInfo['amount']);

            if (strval($value1) != strval(round($value2, 2))) {
                return response()->json([
                    'error' => 'A soma dos valores recebidos da parcela não confere com o valor total da solicitação de pagamento.'
                ], 422);
            }
        }
        return $next($request);
    }
}

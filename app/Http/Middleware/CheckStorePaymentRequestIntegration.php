<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
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
            foreach($paymentRequestInfo['installment_purchase_order'] as $instalmentPurchaseOrder){
                $amountReceived += $instalmentPurchaseOrder['amount_received'];
            }

            if($amountReceived != $paymentRequestInfo['amount']){
                return response()->json([
                    'erro' => 'A soma dos valores recebidos da parcela não confere com o valor total da solicitação de pagamento.'
                ], 422);
            }
        }

        return $next($request);
    }
}

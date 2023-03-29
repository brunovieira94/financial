<?php

namespace App\Http\Middleware;

use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallmentsClean;
use Closure;
use Illuminate\Http\Request;

class CheckIntegrationInstallment
{
    private $paymentRequest;

    public function __construct(PaymentRequest $paymentRequest)
    {
        $this->paymentRequest = $paymentRequest;
    }

    public function handle(Request $request, Closure $next)
    {
        //$request = Request::class;

        $paymentRequestInfo = $request->all();

        $idsInstallments = [];

        if (array_key_exists('installments_linked', $paymentRequestInfo)) {
            foreach ($paymentRequestInfo['installments_linked'] as $installment) {
                if (array_key_exists('id', $installment)) {
                    array_push($idsInstallments, $installment['id']);
                }
            }
            foreach ($idsInstallments as $id) {
                if (PaymentRequestHasInstallmentsClean::where('payment_request_id', request()->route('id'))->where('id', $id)->exists()) {

                    return response()->json([
                        'error' => 'Não é possível agrupar as parcelas da própria solicitação de pagamento.'
                    ], 422);
                }
            }
        }

        return $next($request);
    }
}

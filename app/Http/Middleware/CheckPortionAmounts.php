<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;

class CheckPortionAmounts
{
    private $paymentRequest;

    public function __construct(PaymentRequest $paymentRequest)
    {
        $this->paymentRequest = $paymentRequest;
    }

    public function handle(Request $request, Closure $next)
    {
        $paymentRequestInfo = $request->all();
        $amount = 0;

        if(array_key_exists('id', $request->route()->parameters())){
            $id = (int)$request->route()->parameters()['id'];
            $paymentRequest = $this->paymentRequest->findOrFail($id);
        }

        if(array_key_exists('amount', $paymentRequestInfo)){
            $amount = $request->amount;
        } else {
            $amount = $paymentRequest->amount;
        }

        if(array_key_exists('installments', $paymentRequestInfo)){
            $parcelSum = 0;
            foreach($paymentRequestInfo['installments'] as $installments){
                if(array_key_exists('portion_amount', $installments)){
                    $parcelSum += $installments['portion_amount'];
                }
            }
            if (number_format($amount, 2) != number_format($parcelSum, 2)) {
                return response()->json([
                    'erro' => 'Verifique o valor total das parcelas.'
                ], 422);
            }
        }
        return $next($request);
    }
}

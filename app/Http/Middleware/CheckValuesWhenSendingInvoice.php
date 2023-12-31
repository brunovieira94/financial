<?php

namespace App\Http\Middleware;
use App\Models\PaymentRequest;
use Closure;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isNull;

class CheckValuesWhenSendingInvoice
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
        $sumTax = 0;
        $netValue = 0;

        if(array_key_exists('id', $request->route()->parameters())){
            $id = (int)$request->route()->parameters()['id'];
            $paymentRequest = $this->paymentRequest->findOrFail($id);
        }

        if(array_key_exists('amount', $paymentRequestInfo)){
            $amount = $request->amount;
        } else {
            $amount = $paymentRequest->amount;
        }

        if(array_key_exists('net_value', $paymentRequestInfo)){
            $netValue = $paymentRequestInfo['net_value'];
        } else {
            $netValue = $paymentRequest->net_value ?? 0;
        };

        if(array_key_exists('tax', $paymentRequestInfo)){
            foreach($paymentRequestInfo['tax'] as $tax){
                $sumTax += $tax['tax_amount'];
            }
        }

        if((number_format($netValue + $sumTax, 2)) != number_format($amount, 2)){
            return response()->json([
                'error' => 'A soma do valor líquido acrescido das taxas informadas não corresponde ao valor bruto.'
            ], 422);
        }

        return $next($request);
    }
}

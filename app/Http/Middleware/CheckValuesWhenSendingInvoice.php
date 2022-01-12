<?php

namespace App\Http\Middleware;
use App\Models\PaymentRequest;
use Closure;
use Illuminate\Http\Request;

class CheckValuesWhenSendingInvoice
{
    private $paymentRequest;

    public function __construct(PaymentRequest $paymentRequest)
    {
        $this->paymentRequest = $paymentRequest;
    }

    public function handle(Request $request, Closure $next)
    {
        $tax = $request->all();
        $amount = 0;
        $sumTax = 0;
        $netValue = 0;

        if(array_key_exists('tax', $tax)){
            foreach($tax['tax'] as $taxInfo){
                $sumTax += $taxInfo['tax_amount'];
            }

            if(array_key_exists('net_value', $tax)){
                $netValue = $tax['net_value'];
            } else {
                $id = (int)$request->route()->parameters()['id'];
                $paymentRequest = $this->paymentRequest->findOrFail($id);
                $netValue = $paymentRequest->net_value;
                $amount = $paymentRequest->amount;
            }
            if(array_key_exists('amount', $tax)){
                $amount = $request->amount;
            }

            if(($netValue + $sumTax) != $amount){
                return response('A soma do valor líquido acrescido das taxas informadas não corresponde ao valor bruto', 422)->send();
            }
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;
use App\Models\BillToPay;

use Closure;
use Illuminate\Http\Request;

class CheckValuesWhenSendingInvoice
{
    private $billToPay;

    public function __construct(BillToPay $billToPay)
    {
        $this->billToPay = $billToPay;
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
                $billToPay = $this->billToPay->findOrFail($id);
                $netValue = $billToPay->net_value;
                $amount = $billToPay->amount;
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

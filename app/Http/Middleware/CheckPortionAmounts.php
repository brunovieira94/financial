<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BillToPay;

class CheckPortionAmounts
{
    private $billToPay;

    public function __construct(BillToPay $billToPay)
    {
        $this->billToPay = $billToPay;
    }


    public function handle(Request $request, Closure $next)
    {

        $installments = $request->all();
        $amount = 0;

        if(array_key_exists('amount', $installments)){
            $amount = $request->amount;
        } else {
            $id = (int)$request->route()->parameters()['id'];
            $billToPay = $this->billToPay->findOrFail($id);
            $amount = $billToPay->amount;
        }

        if(array_key_exists('installments', $installments)){
            $parcelSum = 0;
            foreach($installments['installments'] as $installments){
                if(array_key_exists('portion_amount', $installments)){
                    $parcelSum += $installments['portion_amount'];
                }
            }
            if ($amount != $parcelSum) {
                return response('Verifique o valor total das parcelas', 422);
            }
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasInstallments;

class CheckDataRequestGenerateCNAB
{
    private $installment;

    public function __construct(PaymentRequestHasInstallments $installment)
    {
        $this->installment = $installment;
    }

    public function handle(Request $request, Closure $next)
    {
        $requestInfo = $request->all();
        $validationGroupPayment = false;
        $installmentsError = [];

        if (array_key_exists('installments_ids', $requestInfo)) {
            $validationGroupPayment = $this->installment->whereIn('id', $requestInfo['installments_ids'])->where('group_form_payment_id', 7)->exists();
        }


        if ($validationGroupPayment) {
            foreach ($this->installment->whereIn('id', $requestInfo['installments_ids'])->get() as $installment) {
                $installmentsError[] = $installment->payment_request_id;
            }

            return response()->json([
                'error' => 'Não é possível gerar CNAB para parcelas definidas como "Cartão de Crédito" verifica as contas: ' . implode(', ', array_unique($installmentsError))
            ], 422);
        }


        return $next($request);
    }
}

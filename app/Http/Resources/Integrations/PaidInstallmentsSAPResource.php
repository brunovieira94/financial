<?php

namespace App\Http\Resources\Integrations;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Exports\Utils as ExportUtils;

class PaidInstallmentsSAPResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $cnabGenerated = isset($this->cnab_generated_installment)
            ? $this->cnab_generated_installment->generated_cnab
            : null;

        $lastOtherPayment = is_null($cnabGenerated) ? $this->other_payments->last() : null;

        return [
            'OVPM' => [
                'TipOP' => "A",
                'VATRegNum' => $this->companyCNPJ($cnabGenerated, $lastOtherPayment),
                'DocDate' => $this->onlyDate($this->payment_request->created_at),
                'TaxDate' => $this->payment_request->emission_date ?? $this->competence_date,
                'DocDueDate' => $this->due_date,
                'U_RBH_CodPnSisOrig' => $this->payment_request->provider->id,
                'TaxId0' => $this->paymet_request->provider->cnpj ?? "",
                'TaxId4' => $this->paymet_request->provider->cpf ?? "",
                'DocCur' => $this->docCurrencySym($cnabGenerated, $lastOtherPayment),
                'DocRate' => $this->docExchangeRate($cnabGenerated, $lastOtherPayment),
                'JrnlMemo' => $this->payment_request->chart_of_accounts->title,
                'VPM2' => [
                    'Serial' => $this->bar_code ?? "",
                    'U_RBH_IdTransOrig' => $this->payment_request_id,
                    'InstId' => $this->parcel_number,
                    'SumApplied' => ExportUtils::installmentTotalFinalValue($this),
                    'Desc' => $this->discount,
                    'Juros' => $this->fees,
                    'Taxas' => $this->fine,
                    'TrsfrAcct' => $this->payment_request->chart_of_accounts->code,
                ]
            ]
        ];
    }

    private function onlyDate($datetime)
    {
        return isset($datetime) ? explode(' ', $datetime)[0] : '';
    }

    private function companyCNPJ($cnabGenerated, $lastOtherPayment)
    {
        return isset($cnabGenerated) ? $cnabGenerated->company->cnpj : $lastOtherPayment->bank_account_company->cpf_cnpj;
    }

    private function docExchangeRate($cnabGenerated, $lastOtherPayment)
    {
        if (isset($cnabGenerated)) {
            return $this->payment_request->exchange_rate;
        }

        $lastOtherPaymentExchangeInfo = $lastOtherPayment->exchange_rates->first();
        return isset($lastOtherPaymentExchangeInfo) ? $lastOtherPaymentExchangeInfo->exchange_rate : '';
    }

    private function docCurrencySym($cnabGenerated, $lastOtherPayment)
    {
        if (isset($cnabGenerated)) {
            return $this->payment_request->currency->currency_symbol ?? 'R$';
        }

        $lastOtherPaymentExchangeInfo = $lastOtherPayment->exchange_rates->first();
        return isset($lastOtherPaymentExchangeInfo) ? $lastOtherPaymentExchangeInfo->currency->currency_symbol : 'R$';
    }
}

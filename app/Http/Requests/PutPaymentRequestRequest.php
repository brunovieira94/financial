<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutPaymentRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'provider_id' => 'integer',
            'emission_date' => 'Date',
            'pay_date'  => 'Date',
            'bank_account_provider_id' => 'integer',
            'bank_account_company_id' => 'integer',
            'amount' => 'numeric',
            'business_id' => 'integer',
            'cost_center_id' => 'integer',
            'chart_of_account_id' => 'integer',
            'currency_id' => 'integer',
            'exchange_rate' => 'numeric',
            'frequency_of_installments' => 'integer',
            'invoice_number' => 'max:150',
            'type_of_tax' => 'max:150',
            'tax_amount' => 'numeric',
            'net_value' => 'numeric',
            'bar_code' => 'max:150',
            'tax.*.type_of_tax_id' => 'integer',
            'tax.*.tax_amount' => 'numeric',
        ];
    }
}

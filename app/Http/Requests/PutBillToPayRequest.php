<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutBillToPayRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_provider' => 'integer',
            'emission_date' => 'Date',
            'pay_date'  => 'Date',
            'id_bank_account_provider' => 'integer',
            'id_bank_account_company' => 'integer',
            'amount' => 'numeric',
            'id_business' => 'integer',
            'id_cost_center' => 'integer',
            'id_chart_of_account' => 'integer',
            'id_currency' => 'integer',
            'exchange_rate' => 'numeric',
            'frequency_of_installments' => 'integer',
            'invoice_number' => 'max:150',
            'type_of_tax' => 'max:150',
            'tax_amount' => 'numeric',
            'net_value' => 'numeric',
            'bar_code' => 'max:150',
        ];
    }
}

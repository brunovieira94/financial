<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingCnabParse extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'payment_request_ids' => 'array',
            'company_id' => 'required|integer|exists:companies,id',
            'installments_ids' => 'array',
            'all' => 'required|boolean'
        ];
    }
}

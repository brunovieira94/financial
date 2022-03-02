<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingItauCNAB240Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'payment_request_ids' => 'required|array',
            'code_cnab' => 'required_if:payment_type,0',
            'payment_type' => 'required|integer',
            'company_id' => 'required|integer|exists:companies,id',
        ];
    }
}

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
            'code_cnab' => 'required',
            'company_id' => 'required|integer|exists:companies,id',
        ];
    }
}

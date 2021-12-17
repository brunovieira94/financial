<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CheckCNABItauWallet;

class ShippingItauCNAB240Request extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id', new CheckCNABItauWallet],
            'bill_to_pay_ids' => 'required|array',
            'company_id' => 'required|integer|exists:companies,id',
        ];
    }
}

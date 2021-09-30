<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_name' => 'required|max:250',
            'trade_name' => 'max:150',
            'cnpj' => 'max:45',
            'cep' => 'max:10',
            'cities_id' => 'integer',
            'address' => 'max:250',
            'number' => 'max:250',
            'complement' => 'max:150',
            'district' => 'max:150',
            'bank_accounts.*.agency_number' => 'required_without_all:agency_check_number,account_number,account_check_number,bank_id|integer',
            'bank_accounts.*.agency_check_number' => 'integer|required_without_all:agency_number,account_number,account_check_number,bank_id',
            'bank_accounts.*.account_number' => 'integer|required_without_all:agency_number,agency_check_number,account_check_number,bank_id',
            'bank_accounts.*.account_check_number' => 'integer|required_without_all:agency_number,agency_check_number,account_number,bank_id',
            'bank_accounts.*.bank_id' => 'integer|required_without_all:agency_number,agency_check_number,account_number,account_check_number',
            'managers' => 'array',
        ];
    }
}

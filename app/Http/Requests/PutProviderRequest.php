<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutProviderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_name' => 'max:250',
            'trade_name' => 'max:150',
            'cnpj' => 'max:17',
            'responsible' => 'max:250',
            'provider_categories_id' => 'max:17|integer',
            'cost_center_id' => 'integer',
            'cep' => 'max:10',
            'cities_id' => 'integer',
            'address' => 'max:250',
            'number' => 'max:250',
            'complement' => 'max:150',
            'district' => 'max:150',
            'email' => 'max:250',
            'responsible_phone' => 'max:250',
            'responsible_email' => 'max:250',
            'state_subscription' => 'max:250',
            'chart_of_accounts_id' => 'integer',
            'bank_accounts.*.agency_number' => 'required_without_all:agency_check_number,account_number,account_check_number,bank_id,bank_accounts.*.id|integer',
            'bank_accounts.*.account_number' => 'integer|required_without_all:agency_number,agency_check_number,account_check_number,bank_id,bank_accounts.*.id',
            'bank_accounts.*.account_check_number' => 'integer|required_without_all:agency_number,agency_check_number,account_number,bank_id,bank_accounts.*.id',
            'bank_accounts.*.bank_id' => 'integer|required_without_all:agency_number,agency_check_number,account_number,account_check_number,bank_accounts.*.id',
        ];
    }
}

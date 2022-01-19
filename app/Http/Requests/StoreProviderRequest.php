<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'provider_type' => 'required|max:1|in:F,J',
            'company_name' => 'required_if:provider_type,==,J|max:250|prohibited_if:provider_type,==,F',
            'trade_name' => 'max:150|prohibited_if:provider_type,==,F',
            'alias' => 'max:150',
            'cnpj' => 'required_if:provider_type,==,J|max:17|prohibited_if:provider_type,==,F|unique:providers,cnpj,NULL,id,deleted_at,NULL',
            'responsible' => 'max:250',
            'provider_categories_id' => 'required|integer',
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
            'state_subscription' => 'max:250|prohibited_if:provider_type,==,F',
            'chart_of_accounts_id' => 'integer',
            'bank_accounts.*.agency_number' => 'required_without_all:bank_accounts.*.pix_key|numeric',
            'bank_accounts.*.agency_check_number' => 'integer|required_without_all:bank_accounts.*.pix_key',
            'bank_accounts.*.account_number' => 'numeric|required_without_all:bank_accounts.*.pix_key',
            'bank_accounts.*.account_check_number' => 'integer|required_without_all:bank_accounts.*.pix_key',
            'bank_accounts.*.bank_id' => 'integer|required_without_all:bank_accounts.*.pix_key',
            'bank_accounts.*.pix_key' => 'string|required_without_all:bank_accounts.*.agency_number,bank_accounts.*.agency_check_number,bank_accounts.*.account_number,bank_accounts.*.account_check_number,bank_accounts.*.account_type,bank_accounts.*.bank_id',
            'bank_accounts.*.pix_key_type' => 'integer|required_without_all:bank_accounts.*.agency_number,bank_accounts.*.agency_check_number,bank_accounts.*.account_number,bank_accounts.*.account_check_number,bank_accounts.*.account_type,bank_accounts.*.bank_id|min:0|max:4',
            'bank_accounts.*.account_type' => 'integer|required_without_all:bank_accounts.*.pix_key|min:0|max:2',
            //validation physical person
            'cpf' => 'required_if:provider_type,==,F|numeric|digits:11|prohibited_if:provider_type,==,J|unique:providers,cpf,NULL,id,deleted_at,NULL',
            'rg' => 'required_if:provider_type,==,F|string|prohibited_if:provider_type,==,J',
            'full_name' => 'required_if:provider_type,==,F|string|max:255|prohibited_if:provider_type,==,J',
            'birth_date' => 'required_if:provider_type,==,F|date|max:255|prohibited_if:provider_type,==,J',
        ];
    }
}

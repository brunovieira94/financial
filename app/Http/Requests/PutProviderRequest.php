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
            'provider_type' => 'max:1|in:F,J',
            'company_name' => 'max:250',
            'international' => 'boolean',
            'trade_name' => 'max:150',
            'alias' => 'max:150',
            'cnpj' => 'max:17',
            'responsible' => 'max:250',
            'provider_categories_id' => 'integer',
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
            'state_subscription' => 'max:250|prohibited_if:provider_type,==,F|required_without:city_subscription',
            'city_subscription' => 'max:250|prohibited_if:provider_type,==,F|required_without:state_subscription',
            'accept_billet_payment' => 'boolean',
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
            'cpf' => 'numeric|digits:11|prohibited_if:provider_type,==,J',
            'rg' => 'string|prohibited_if:provider_type,==,J',
            'full_name' => 'string|max:255|prohibited_if:provider_type,==,J',
            'birth_date' => 'date|max:255|prohibited_if:provider_type,==,J',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ProviderCitySubscription;
use App\Rules\ProviderStateSubscription;
use App\Rules\ProviderCNPJ;
use App\Rules\ProviderUniqueCNPJ;
use Illuminate\Validation\Rule;

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
            'cnpj' => [new ProviderCNPJ(request()->input('international'),request()->input('provider_type')), 'max:17', 'prohibited_if:provider_type,==,F',
                Rule::unique('providers', 'cnpj')
                ->where(static function ($query) {
                    return $query->whereNotNull('cnpj')->whereNull('deleted_at');
                })
                ->ignore($this->id),
            ],
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
            'state_subscription' => ['max:250', 'prohibited_if:provider_type,==,F', new ProviderStateSubscription(request()->input('international'),request()->input('city_subscription'),request()->input('provider_type'))],
            'city_subscription' => ['max:250', 'prohibited_if:provider_type,==,F', new ProviderCitySubscription(request()->input('international'),request()->input('state_subscription'),request()->input('provider_type'))],
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
            'international' => 'boolean',
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->has('city_subscription')){
            $this->merge(['city_subscription'=>null]);
        }
        if (!$this->has('state_subscription')){
            $this->merge(['state_subscription'=>null]);
        }
        if (!$this->has('cnpj') && $this->provider_type == 'J' && !$this->international){
            $this->merge(['cnpj'=>null]);
        }
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutCompanyRequest extends FormRequest
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
            'cnpj' => 'max:45|unique:companies,cnpj,' . $this->id . ',id,deleted_at,NULL',
            'cep' => 'max:10',
            'cities_id' => 'integer|exists:cities,id',
            'address' => 'max:250',
            'number' => 'max:250',
            'complement' => 'max:150',
            'district' => 'max:150',
            'bank_accounts.*.agency_number' => 'required_without_all:bank_accounts.*.pix_key|numeric',
            //'bank_accounts.*.agency_check_number' => 'required_without_all:bank_accounts.*.pix_key',
            'bank_accounts.*.account_number' => 'numeric|required_without_all:bank_accounts.*.pix_key',
            //'bank_accounts.*.account_check_number' => 'required_without_all:bank_accounts.*.pix_key',
            'bank_accounts.*.bank_id' => 'integer|required_without_all:bank_accounts.*.pix_key|exists:banks,id',
            'bank_accounts.*.pix_key' => 'string|required_without_all:bank_accounts.*.agency_number,bank_accounts.*.agency_check_number,bank_accounts.*.account_number,bank_accounts.*.account_check_number,bank_accounts.*.account_type,bank_accounts.*.bank_id',
            'bank_accounts.*.pix_key_type' => 'integer|required_without_all:bank_accounts.*.agency_number,bank_accounts.*.agency_check_number,bank_accounts.*.account_number,bank_accounts.*.account_check_number,bank_accounts.*.account_type,bank_accounts.*.bank_id|min:0|max:4',
            'bank_accounts.*.account_type' => 'integer|required_without_all:bank_accounts.*.pix_key|min:0|max:2',
            'managers' => 'array',
            'bank_accounts.*.default_bank' => 'boolean',
            'cpf_cnpj' => 'max:191|in:F,J',
            'entity_name' => 'max:191',
            'entity_type' => 'max:1',
        ];
    }
}

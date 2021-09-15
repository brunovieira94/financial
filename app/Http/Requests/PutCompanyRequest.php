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
            'cpnj' => 'max:45',
            'bank_accounts.*.agency_number' => 'required_without_all:agency_check_number,account_number,account_check_number,bank_id,bank_accounts.*.id|integer',
            'bank_accounts.*.account_number' => 'integer|required_without_all:agency_number,agency_check_number,account_check_number,bank_id,bank_accounts.*.id',
            'bank_accounts.*.account_check_number' => 'integer|required_without_all:agency_number,agency_check_number,account_number,bank_id,bank_accounts.*.id',
            'bank_accounts.*.bank_id' => 'integer|required_without_all:agency_number,agency_check_number,account_number,account_check_number,bank_accounts.*.id',
        ];
    }
}

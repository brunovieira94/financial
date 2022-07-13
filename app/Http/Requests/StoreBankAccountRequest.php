<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'agency_number' => 'required_without_all:bank_accounts.*.pix_key|numeric',
            //'agency_check_number' => 'required_without_all:bank_accounts.*.pix_key',
            'account_number' => 'numeric|required_without_all:bank_accounts.*.pix_key',
            //'account_check_number' => 'required_without_all:bank_accounts.*.pix_key',
            'bank_id' => 'integer|required_without_all:bank_accounts.*.pix_key|exists:banks,id',
            'pix_key' => 'string|required_without_all:bank_accounts.*.agency_number,bank_accounts.*.agency_check_number,bank_accounts.*.account_number,bank_accounts.*.account_check_number,bank_accounts.*.account_type,bank_accounts.*.bank_id',
            'pix_key_type' => 'integer|required_without_all:bank_accounts.*.agency_number,bank_accounts.*.agency_check_number,bank_accounts.*.account_number,bank_accounts.*.account_check_number,bank_accounts.*.account_type,bank_accounts.*.bank_id|min:0|max:4',
            'account_type' => 'integer|required_without_all:bank_accounts.*.pix_key|min:0|max:2',
       ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutBankAccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'agency_number' => 'required_without_all:agency_check_number,account_number,account_check_number,bank_id|numeric',
            'agency_check_number' => 'integer|required_without_all:agency_number,account_number,account_check_number,bank_id',
            'account_number' => 'integer|required_without_all:agency_number,agency_check_number,account_check_number,bank_id',
            'account_check_number' => 'integer|required_without_all:agency_number,agency_check_number,account_number,bank_id',
            'bank_id' => 'integer|required_without_all:agency_number,agency_check_number,account_number,account_check_number',
       ];
    }
}

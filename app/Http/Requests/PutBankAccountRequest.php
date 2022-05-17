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
            'agency_number' => 'required_without_all:pix_key|numeric',
            'agency_check_number' => 'integer|required_without_all:pix_key',
            'account_number' => 'numeric|required_without_all:pix_key',
            'account_check_number' => 'integer|required_without_all:pix_key',
            'bank_id' => 'integer|required_without_all:pix_key|exists:banks,id',
            'pix_key' => 'string|required_without_all:agency_number,agency_check_number,account_number,account_check_number,account_type,bank_id',
            'pix_key_type' => 'integer|required_without_all:agency_number,agency_check_number,account_number,account_check_number,account_type,bank_id|min:0|max:4',
            'account_type' => 'integer|required_without_all:pix_key|min:0|max:2',
       ];
    }
}

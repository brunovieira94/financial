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
            'agency_number' => 'required_without_all:pix_key|numeric',
            'agency_check_number' => 'integer|required_without_all:pix_key',
            'account_number' => 'integer|required_without_all:pix_key',
            'account_check_number' => 'integer|required_without_all:pix_key',
            'bank_id' => 'integer|required_without_all:pix_key',
            'pix_key' => 'string|required_without_all:agency_number,agency_check_number,account_number,account_check_number,account_type,bank_id',
            'account_type' => 'integer|required_without_all:pix_key|min:0|max:2',
       ];
    }
}

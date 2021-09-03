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
            'agency_number' => 'integer',
            'agency_check_number' => 'integer',
            'account_number' => 'integer',
            'account_check_number' => 'integer',
            'bank_id' => 'integer',
       ];
    }
}

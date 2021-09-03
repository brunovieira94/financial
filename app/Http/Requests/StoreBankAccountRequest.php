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
                'agency_number' => 'required|integer',
                'agency_check_number' => 'required|integer',
                'account_number' => 'required|integer',
                'account_check_number' => 'required|integer',
                'bank_id' => 'required|integer',
       ];
    }
}

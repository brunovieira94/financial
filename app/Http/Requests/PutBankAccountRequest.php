<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
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

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutBankAccountRequest extends FormRequest
{
<<<<<<< HEAD
=======
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
>>>>>>> develop
    public function authorize()
    {
        return true;
    }

<<<<<<< HEAD
=======
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
>>>>>>> develop
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

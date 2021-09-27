<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutBankRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'max:150',
            'cnpj' => 'max:45',
            'cnab400' => 'boolean',
            'cnab240' => 'boolean',
            'bank_code' => 'numeric',
        ];
    }
}

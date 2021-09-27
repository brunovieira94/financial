<?php

namespace App\Http\Requests;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'bank_code' => 'numeric',
            'cnpj' => 'required|max:45',
            'title' => 'required|max:150',
            'cnab400' => 'required|boolean',
            'cnab240' => 'required|boolean',
        ];
    }

}

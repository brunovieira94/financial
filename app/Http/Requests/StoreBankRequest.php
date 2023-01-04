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
            'bank_code' => 'string',
            'title' => 'required|max:150|unique:banks,title,NULL,id,deleted_at,NULL',
            'cnab400' => 'required|boolean',
            'cnab240' => 'required|boolean',
        ];
    }

    public function attributes(){
        return [
            'title' => 'banco',
        ];
    }

}

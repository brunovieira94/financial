<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255||unique:currency,title,NULL,id,deleted_at,NULL',
            'initials' => 'required|max:255',
            'default' => 'boolean',
            'currency_symbol' => 'required|max:255',
        ];
    }
}

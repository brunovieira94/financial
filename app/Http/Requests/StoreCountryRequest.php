<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCountryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:150|unique:countries,title,' . $this->id . ',id,deleted_at,NULL',
        ];
    }

    public function messages()
    {
        return [
            'title.unique'  => 'Este país já está cadastrado no sistema.',
        ];
    }
}

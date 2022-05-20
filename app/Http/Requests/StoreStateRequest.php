<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'country_id' => 'required|integer|exists:countries,id',
            'title' => 'required|max:150|unique:states,title,NULL,id,deleted_at,NULL',
            'uf' => 'required|size:2',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'estado',
        ];
    }
}

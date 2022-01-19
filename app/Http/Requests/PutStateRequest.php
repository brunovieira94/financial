<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutStateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'country_id' => 'required_without:title|integer',
            'uf' => 'required|size:2',
            'title' => 'required_without:country|max:150|unique:states,title,NULL,id,deleted_at,NULL',
        ];
    }
}

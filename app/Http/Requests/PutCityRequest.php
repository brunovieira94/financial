<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutCityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required_without:states_id|max:250',
            'states_id' => 'required_without:title|integer',
        ];
    }
}

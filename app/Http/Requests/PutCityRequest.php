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
            'title' => 'max:250',
            'states_id' => 'integer',
        ];
    }
}

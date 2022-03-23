<?php

namespace App\Http\Requests;

use App\Rules\DuplicateCity;
use Illuminate\Foundation\Http\FormRequest;

class StoreCityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => ['required', 'max:250', new DuplicateCity(request()->input('states_id'))],
            'states_id' => 'required|integer',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'cidade',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\DuplicateCity;

class PutCityRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => ['required_without:states_id', 'max:250', new DuplicateCity(request()->input('states_id'), Request::instance()->id)],
            'states_id' => 'required_without:title|integer',
        ];
    }
}

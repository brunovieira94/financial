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
            'country' => 'required_without:title|max:2',
            'title' => 'required_without:country|max:150',
        ];
    }
}

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
            'country' => 'max:2',
            'title' => 'max:150',
        ];
    }
}

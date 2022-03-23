<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'max:150',
            'role_id' => 'integer',
            'phone' => 'string',
            'extension' => 'string',
            'email' => 'email|max:150',
            'password' => 'max:250|min:8',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'nome',
        ];
    }
}

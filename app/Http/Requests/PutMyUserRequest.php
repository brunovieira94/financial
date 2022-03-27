<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutMyUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'role_id' => 'prohibited',
            'email' => 'prohibited',
            'password' => 'required',
            'name' => 'max:150',
            'phone' => 'string',
            'extension' => 'string',
            'email' => 'email|max:150',
            'password' => 'max:250|min:8',
            'new-password' => 'max:250|min:8'
        ];
    }
}

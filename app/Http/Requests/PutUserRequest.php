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
            'role_id' => 'integer|exists:role,id',
            'phone' => 'string',
            'extension' => 'string',
            'email' => 'email|max:150|unique:users,email,' . $this->id . ',id,deleted_at,NULL',
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

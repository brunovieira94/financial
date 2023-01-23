<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email|exists:users',
            'token' => 'required|string|exists:password_resets',
            'password' => 'required|max:250|min:8|confirmed',
            'password_confirmation' => 'required|max:250|min:8'
        ];
    }
}

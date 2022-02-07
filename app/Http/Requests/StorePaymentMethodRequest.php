<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255|unique:payment_method,title,' . $this->id . ',id,deleted_at,NULL',
            'initials' => 'required|max:255',
        ];
    }
}

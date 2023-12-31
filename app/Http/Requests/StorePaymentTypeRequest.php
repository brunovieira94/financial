<?php

namespace App\Http\Requests;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255|unique:payment_types,title,' . $this->id . ',id,deleted_at,NULL',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'tipo de pagamento',
        ];
    }

}

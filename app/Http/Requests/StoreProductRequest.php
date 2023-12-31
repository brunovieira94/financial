<?php

namespace App\Http\Requests;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255|unique:products,title,NULL,id,deleted_at,NULL',
            'measurement_units_id' => 'required|integer|exists:measurement_units,id',
            'chart_of_accounts_id' => 'required|integer|exists:chart_of_accounts,id',
            'description' => 'required',
            'attributes' => 'array',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'produto',
        ];
    }

}

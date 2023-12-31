<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class PutProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'max:255|unique:products,title,' . $this->id . ',id,deleted_at,NULL',
            'measurement_units_id' => 'integer|exists:measurement_units,id',
            'chart_of_accounts_id' => 'integer|exists:chart_of_accounts,id',
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

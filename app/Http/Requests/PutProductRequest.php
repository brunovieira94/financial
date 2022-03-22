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
            'title' => 'max:255|unique:products,title,NULL,id,deleted_at,NULL',
            'measurement_units_id' => 'integer',
            'chart_of_accounts_id' => 'integer',
            'attributes' => 'array',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'produto',
            'measurement_units_id' => 'unidade de medida',
            'chart_of_accounts_id' => 'plano de contas',
            'attributes' => 'atributos',
        ];
    }

}

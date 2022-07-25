<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'request_type' => 'required|integer|min:0|max:2',
            'status' => 'integer|min:0|max:5',
            'cost_centers' => 'array|min:1',
            'cost_centers.*.cost_center_id' => 'required|integer|exists:cost_center,id',
            'cost_centers.*.percentage' => 'required|integer',
            'attachments' => 'array',
            'services' => 'array|min:1',
            'services.*.service_id' => 'required|integer|exists:services,id',
            'services.*.contract_duration' => 'required|integer',
            'services.*.contract_type' => 'required|integer',
            'products' => 'array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            //'products.*.quantity' => 'required|integer',
            'company_id' => 'required|integer|exists:companies,id',
            'observations' => 'required|min:3'
        ];
    }

    public function messages()
    {
        return [
            'observations' => 'O campo descrição é obrigatório.',
            'cost_centers.*.cost_center_id.required' => 'O campo centro de custo é obrigatório.',
            'cost_centers.*.percentage.required' => 'O campo porcentagem é obrigatório.',
            'services.*.service_id.required' => 'O campo serviço é obrigatório.',
            'services.*.contract_duration.required' => 'O campo tempo de duração do contrato é obrigatório.',
            'services.*.contract_type.required' => 'O campo tipo de duração é obrigatório.',
            'products.*.product_id.required' => 'O campo produto é obrigatório.',
            //'products.*.quantity.required' => 'O campo quantidade é obrigatório.',
        ];
    }
}

<?php

namespace App\Http\Requests;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'order_type' => 'required|boolean',
            'provider_id' => 'required|integer',
            'currency_id' => 'required|integer',
            'exchange_rate' => 'required|numeric',
            'initial_total_value' => 'required|numeric',
            'negotiated_total_value' => 'required|numeric',
            'billing_date' => 'required|Date',
            'payment_condition' => 'required|integer',
            'cost_centers' => 'array',
            'attachments' => 'array',
            'services' => 'array',
            'products' => 'array',
        ];
    }

}

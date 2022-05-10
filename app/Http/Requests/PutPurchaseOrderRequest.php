<?php

namespace App\Http\Requests;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class PutPurchaseOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'order_type' => 'required|integer|min:0|max:2',
            'provider_id' => 'required|integer',
            'currency_id' => 'required|integer',
            'exchange_rate' => 'numeric',
            'frequency_of_installments' => 'required|integer',
            'installments_quantity' => 'required|integer',
            'unique_discount' => 'boolean',
            'billing_date' => 'required_without:payment_condition|Date',
            'initial_date' => 'required|date',
            'payment_condition' => 'required_without:billing_date|integer',
            'cost_centers' => 'array',
            'attachments' => 'array',
            'services' => 'array',
            'products' => 'array',
            'company_id' => 'required|integer',
            'percentage_discount_services' => 'numeric',
            'money_discount_services' => 'numeric',
            'percentage_discount_products' => 'numeric',
            'money_discount_products' => 'numeric',
            'increase_tolerance' => 'numeric',
            'unique_product_discount' => 'boolean',
        ];
    }

}

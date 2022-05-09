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
            'cost_centers' => 'array',
            'attachments' => 'array',
            'services' => 'array',
            'products' => 'array',
            'company_id' => 'required|integer',
        ];
    }

}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderQuotationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            /*'quotation_items' => 'required|array|min:1',
            'quotation_items.*.services' => 'array|min:1',
            'quotation_items.*.products' => 'array|min:1',
            'quotation_items.provider_id' => 'required|integer|exists:providers,id',
            'quotation_items.*.*.*.service_id' => 'required|integer|exists:services,id',
            'quotation_items.*.*.*.products_id' => 'required|integer|exists:products,id',*/];
    }

    public function messages()
    {
        return [
            /*'quotation_items.*' => 'Deve ter pelo menos 1 cotação',
            'quotation_items.*.provider_id' => 'O campo centro de custo 2 é obrigatório.',
            'quotation_items.*.provider_id.required' => 'O campo centro de custo é obrigatório.',*/];
    }
}

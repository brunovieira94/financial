<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reserve' => 'required|max:150',
            'partner_value' => 'required|max:150',
            'pay_date' => 'required|date',
            'boleto_value' => 'max:150',
            'boleto_code' => 'max:150',
            'recipient_name' => 'required|max:150 ',
            'oracle_protocol' => 'required|max:150 ',
            'cnpj' => 'required|max:150 ',
        ];
    }
}

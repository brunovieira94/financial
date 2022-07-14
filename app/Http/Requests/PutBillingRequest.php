<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutBillingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reserve' => 'required|max:150',
            'cangooroo_booking_id' => 'required|integer',
            'payment_status' => 'max:150',
            'status_123' => 'max:150',
            'supplier_value' => 'required|max:150',
            'pay_date' => 'required|date',
            'boleto_value' => 'max:150',
            'boleto_code' => 'max:150',
            'oracle_protocol' => 'required|max:150 ',
            'cnpj' => 'required|max:150 ',
            'reason' => 'max:255',
            'reason_to_reject_id' => 'prohibited',
            'approval_status' => 'prohibited',
            'reason' => 'prohibited',
        ];
    }
}

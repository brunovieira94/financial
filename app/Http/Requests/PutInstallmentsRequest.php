<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutInstallmentsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_request_id' => 'required|integer|exists:payment_requests,id',
            'installments.*.id' => 'integer|required',
            'installments.*.extension_date' => 'date',
            'installments.*.competence_date' => 'date',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

class PutPaymentRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_id' => 'integer',
            'provider_id' => ['integer',]
            'initial_value' => 'numeric',
            'fees' => 'numeric',
            'discount' => 'numeric',
            'percentage_discount' => 'numeric',
            'provider_id' => 'integer',
            'form_payment' => 'max:2',
            'emission_date' => 'Date',
            'pay_date'  => 'Date',
            'bank_account_provider_id' => 'integer',
            'amount' => 'numeric',
            'business_id' => 'integer',
            'cost_center_id' => 'integer',
            'chart_of_account_id' => 'integer',
            'currency_id' => 'integer',
            'exchange_rate' => 'numeric',
            'frequency_of_installments' => 'integer',
            'invoice_number' => ['max:150'],
            'type_of_tax' => 'max:150',
            'tax_amount' => 'numeric',
            'net_value' => 'numeric',
            'bar_code' => ['max:150'],
            'tax.*.type_of_tax_id' => 'integer',
            'tax.*.tax_amount' => 'numeric',
            'force_registration' => 'boolean',
            'xml_file' => [
                'file',
                function ($attribute, $value, $fail) {
                    $xml_file = explode('.', $value->getClientOriginalName());
                    if($xml_file[count($xml_file)-1] != 'xml'){
                        $fail($attribute.'\'s extension is invalid.');
                    }
                },
            ],
            'invoice_type' => 'max:150',
            //installments
            'installments.*.portion_amount' => 'numeric',
            'installments.*.due_date' => 'date',
            'installments.*.pay' => 'boolean',
            'installments.*.extension_date' => 'date',
            'installments.*.competence_date' => 'date',
        ];
    }
}

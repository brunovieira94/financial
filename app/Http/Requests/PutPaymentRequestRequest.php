<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use App\Rules\DuplicatePaymentRequest;
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
            'provider_id' => 'integer',
            'emission_date' => 'Date',
            'pay_date'  => 'Date',
            'bank_account_provider_id' => 'integer',
            'bank_account_company_id' => 'integer',
            'amount' => 'numeric',
            'business_id' => 'integer',
            'cost_center_id' => 'integer',
            'chart_of_account_id' => 'integer',
            'currency_id' => 'integer',
            'exchange_rate' => 'numeric',
            'frequency_of_installments' => 'integer',
            'invoice_number' => 'max:150',
            'type_of_tax' => 'max:150',
            'tax_amount' => 'numeric',
            'net_value' => 'numeric',
            'bar_code' => ['max:150', new DuplicatePaymentRequest(request()->input('business_id') ?? null, request()->input('force_registration') ?? false, Request::instance()->id)],
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
            //installments
            'installments.*.portion_amount' => 'numeric',
            'installments.*.due_date' => 'date',
            'installments.*.pay' => 'boolean',
            'installments.*.note' => 'max:255',
            'installments.*.extension_date' => 'date',
            'installments.*.competence_date' => 'date',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequestRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'provider_id' => 'required|integer',
            'form_payment' => 'max:2',
            'emission_date' => 'required|Date',
            'pay_date'  => 'required|Date',
            'bank_account_provider_id' => 'integer',
            'discount' => 'numeric',
            'percentage_discount' => 'numeric',
            'amount' => 'required|numeric',
            'business_id' => 'required|integer',
            'cost_center_id' => 'required|integer',
            'chart_of_account_id' => 'required|integer',
            'currency_id' => 'required|integer',
            'exchange_rate' => 'numeric',
            'frequency_of_installments' => 'integer',
            'net_value' => 'numeric',
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
            //NF
            'invoice_file' => 'file|required_with_all:invoice_number,type_of_tax,net_value,tax_amount',
            'invoice_number' => ['max:150', 'required_with_all:invoice_file,type_of_tax,net_value,tax_amount'],
            'tax.*.type_of_tax_id' => 'integer|required_with_all:invoice_file,invoice_number,net_value,tax.*.tax_amount',
            'tax.*.tax_amount' => 'numeric|required_with_all:invoice_file,invoice_number,tax.*.id_type_of_tax,net_value',
            //Boleto
            'bar_code' => ['max:150', 'required_with_all:billet_file'],
            'billet_file' => 'file|required_with_all:bar_code',
            //installments
            'installments.*.portion_amount' => 'required_with:installments.*.due_date,installments.*.note,installments.*.pay|numeric',
            'installments.*.due_date' => 'required_with:installments.*.portion_amount,installments.*.note,installments.*.pay|date',
            'installments.*.pay' => 'boolean',
            'installments.*.note' => 'max:255',
            'force_registration' => 'boolean',
            'installments.*.extension_date' => 'date',
            'installments.*.competence_date' => 'date',
        ];
    }
}

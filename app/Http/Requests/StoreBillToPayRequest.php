<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillToPayRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_provider' => 'required|integer',
            'emission_date' => 'required|Date',
            'pay_date'  => 'required|Date',
            'id_bank_account_provider' => 'integer',
            'id_bank_account_company' => 'integer',
            'amount' => 'required|numeric',
            'id_business' => 'required|integer',
            'id_cost_center' => 'required|integer',
            'id_chart_of_account' => 'required|integer',
            'id_currency' => 'required|integer',
            'exchange_rate' => 'numeric',
            'frequency_of_installments' => 'integer',
            'net_value' => 'numeric',
            //NF
            'invoice_file' => 'file|required_with_all:invoice_number,type_of_tax,net_value,tax_amount',
            'invoice_number' => 'max:150|required_with_all:invoice_file,type_of_tax,net_value,tax_amount',
            'tax.*.id_type_of_tax' => 'integer|required_with_all:invoice_file,invoice_number,net_value,tax.*.tax_amount',
            'tax.*.tax_amount' => 'numeric|required_with_all:invoice_file,invoice_number,tax.*.id_type_of_tax,net_value',
            //Boleto
            'bar_code' => 'max:150|required_with_all:billet_file',
            'billet_file' => 'file|required_with_all:bar_code',
            //installments
            'installments.*.portion_amount' => 'required_with:installments.*.due_date,installments.*.note,installments.*.pay|numeric',
            'installments.*.due_date' => 'required_with:installments.*.portion_amount,installments.*.note,installments.*.pay|date',
            'installments.*.pay' => 'boolean',
            'installments.*.note' => 'max:255',
        ];
    }
}

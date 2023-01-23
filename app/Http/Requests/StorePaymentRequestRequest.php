<?php

namespace App\Http\Requests;

use App\Models\Provider;
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
            'company_id' => 'required|integer|exists:companies,id',
            'provider_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    if(!isset($this->installment_purchase_order))
                    {
                        if(!Provider::findOrFail($value)->allows_registration_without_purchase_order)
                        {
                            $fail('O fornecedor selecionado exige que seja informado um pedido de compra para realizar o cadastro dessa solicitação.');
                        }
                    }
                },
                'exists:providers,id',
            ],
            'form_payment' => 'max:2',
            'emission_date' => 'required|Date',
            'pay_date'  => 'required|Date',
            'bank_account_provider_id' => 'integer|exists:bank_accounts,id',
            'discount' => 'numeric',
            'percentage_discount' => 'numeric',
            'amount' => 'required|numeric',
            'business_id' => 'required|integer|exists:business,id',
            'cost_center_id' => 'required|integer|exists:cost_center,id',
            'chart_of_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'currency_id' => 'required|integer|exists:currency,id',
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
            'tax.*.type_of_tax_id' => 'integer|required_with_all:invoice_file,invoice_number,net_value,tax.*.tax_amount|exists:type_of_tax,id',
            'tax.*.tax_amount' => 'numeric|required_with_all:invoice_file,invoice_number,tax.*.id_type_of_tax,net_value',
            //Boleto
            //'bar_code' => ['max:150', 'required_if:payment_type,==,1'],
            //'billet_file' => ['file', 'required_if:payment_type,==,1'],
            //installments
            'installments.*.portion_amount' => 'required_with:installments.*.due_date,installments.*.note,installments.*.pay|numeric',
            'installments.*.due_date' => 'required_with:installments.*.portion_amount,installments.*.note,installments.*.pay|date',
            'installments.*.pay' => 'boolean',
            'force_registration' => 'boolean',
            'installments.*.extension_date' => 'date',
            'installments.*.competence_date' => 'date',
            'installments.*.initial_value' => 'required_with:installments.*.due_date,installments.*.note,installments.*.pay|numeric',
            'installments.*.discount' => 'numeric',
            'installments.*.fees' => 'numeric',
            'installments.*.bar_code' => 'max:150|distinct',
            'installments.*.bank_account_provider_id' => 'integer|exists:bank_accounts,id',
            'installments.*.group_form_payment_id' => 'required|integer|exists:group_form_payment,id',
            'installments.*.billet_number' => 'max:150',
            'installments.*.fine' => 'numeric',
            'installments.*.billet_file' => 'file',
            'purchase_orders.*.order' => 'required_with:installment_purchase_order.*.installment',
            'installment_purchase_order.*.installment' => 'required_with:purchase_orders.*.order',
            'currency_old_id' => 'integer|exists:currency,id',
            'amount_old' => 'numeric',
            'net_value_old' => 'numeric',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Provider;
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
            'company_id' => 'integer|exists:companies,id',
            'provider_id' => [
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
            'initial_value' => 'numeric',
            'fees' => 'numeric',
            'discount' => 'numeric',
            'percentage_discount' => 'numeric',
            'form_payment' => 'max:2',
            'emission_date' => 'Date',
            'pay_date'  => 'Date',
            'bank_account_provider_id' => 'integer',
            'amount' => 'numeric',
            'business_id' => 'integer',
            'cost_center_id' => 'integer',
            'chart_of_account_id' => 'integer|exists:chart_of_accounts,id',
            'currency_id' => 'integer|exists:currency,id',
            //'exchange_rate' => 'numeric',
            'frequency_of_installments' => 'integer',
            'invoice_number' => ['max:150'],
            'type_of_tax' => 'max:150',
            'tax_amount' => 'numeric',
            'net_value' => 'numeric',
            'bar_code' => ['max:150'],
            'tax.*.type_of_tax_id' => 'integer|exists:type_of_tax,id',
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
            'installments.*.bar_code' => 'max:150',
            'installments.*.bank_account_provider_id' => 'integer|exists:bank_accounts,id',
            'installments.*.group_form_payment_id' => 'integer|exists:group_form_payment,id',
            'installments.*.billet_number' => 'max:150',
            'installments.*.fine' => 'numeric',
            'installments.*.billet_file' => 'file',
            'installments.*.card_identifier' => 'max:191',
            'purchase_orders.*.order' => 'required_with:installment_purchase_order.*.installment',
            'installment_purchase_order.*.installment' => 'required_with:purchase_orders.*.order',
            'currency_old_id' => 'integer|exists:currency,id',
            'amount_old' => 'numeric',
            'net_value_old' => 'numeric',
        ];
    }
}

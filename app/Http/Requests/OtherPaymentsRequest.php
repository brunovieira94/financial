<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtherPaymentsRequest extends FormRequest
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
            'group_form_payment_id' => 'required|integer|exists:group_form_payment,id',
            'company_id' => 'integer|exists:companies,id',
            'installments_ids' => 'required|array|exists:payment_requests_installments,id',
            'payment_request_ids' => 'array|exists:payment_requests,id',
            'bank_account_company_id' => 'required|integer|exists:bank_accounts,id',
            'payment_date' => 'required|date',
            'attachments' => 'required|array',
            'exchange_rates' => 'array',
            'note' => 'string|nullable',
            'all' => 'boolean',
        ];
    }
}

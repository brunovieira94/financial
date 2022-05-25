<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PutHotelRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_hotel_cangooroo' => 'required|max:150',
            'id_hotel_omnibees' => 'max:150',
            'hotel_name' => 'required|max:150',
            'chain' => 'max:150',
            'email' => 'required|max:150',
            'email_omnibees' => 'max:150',
            'phone' => 'max:150',
            'billing_type' => 'required|integer|min:0|max:2',
            'payment_type_id' => 'integer|exists:payment_types,id',
            'holder_full_name' => 'max:150',
            'cpf_cnpj' => 'required|max:150',
            'isValid' => 'boolean',
            'bank_accounts.*.agency_number' => 'required_without_all:bank_accounts.*.pix_key|numeric',
            'bank_accounts.*.account_number' => 'numeric|required_without_all:bank_accounts.*.pix_key',
            'bank_accounts.*.bank_id' => 'integer|required_without_all:bank_accounts.*.pix_key|exists:banks,id',
            'bank_accounts.*.pix_key' => 'string|required_without_all:bank_accounts.*.agency_number,bank_accounts.*.agency_check_number,bank_accounts.*.account_number,bank_accounts.*.account_check_number,bank_accounts.*.account_type,bank_accounts.*.bank_id',
            'bank_accounts.*.pix_key_type' => 'integer|required_without_all:bank_accounts.*.agency_number,bank_accounts.*.agency_check_number,bank_accounts.*.account_number,bank_accounts.*.account_check_number,bank_accounts.*.account_type,bank_accounts.*.bank_id|min:0|max:4',
            'bank_accounts.*.account_type' => 'integer|required_without_all:bank_accounts.*.pix_key|min:0|max:2',
        ];
    }
}

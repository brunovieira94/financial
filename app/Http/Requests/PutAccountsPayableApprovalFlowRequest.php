<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PutAccountsPayableApprovalFlowRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reason' => 'max:255'
        ];
    }

    public function attributes()
    {
        return [
            'reason' => 'motivo',
        ];
    }
}

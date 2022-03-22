<?php

namespace App\Http\Requests;

use App\Models\Bank;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class PutBankRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'unique:banks,title,' . $this->id . ',id,deleted_at,NULL',
            'cnab400' => 'boolean',
            'cnab240' => 'boolean',
            'bank_code' => 'numeric',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'banco',
            'bank_code' => 'código do banco',
        ];
    }
}

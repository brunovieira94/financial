<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|max:150|unique:business,name,NULL,id,deleted_at,NULL',
            'company_id' => 'required|integer|exists:companies,id',
            'cost_user.*.id' => 'integer',
            'cost_user.*.cost_center_id' => 'integer',
            'cost_user.*.user_id' => 'integer',
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'negócio',
        ];
    }
}

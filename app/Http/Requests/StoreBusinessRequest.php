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
            'name' => 'required|max:150',
            'company_id' => 'required|integer',
            'cost_user.*.id' => 'integer',
            'cost_user.*.cost_center_id' => 'integer',
            'cost_user.*.user_id' => 'integer',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PutDuplicateRoleCostCenter;

class PutCostCenterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255',
            'parent' => 'nullable|integer|exists:cost_center,id',
            'code' => new PutDuplicateRoleCostCenter(request()->input('parent'), \Request::instance()->id),
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'nome da empresa',
        ];
    }
}

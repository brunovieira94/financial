<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PutDuplicateRoleChartOfAccounts;
use Illuminate\Http\Request;

class PutChartOfAccountsRequest extends FormRequest
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
            'title' => 'required|max:255',
            'parent' => 'nullable|integer',
            'code' => new PutDuplicateRoleChartOfAccounts(request()->input('parent'), Request::instance()->id),
            'accounting_title' => 'nullable|max:255',
            'group_title' => 'nullable|max:255',
            'referential_title' => 'nullable|max:255',
            'group' => 'nullable|integer',
        ];
    }
}

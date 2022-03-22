<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PutDuplicateRoleChartOfAccounts;
use Illuminate\Http\Request;

class PutChartOfAccountsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255',
            'parent' => 'nullable|integer',
            'code' => new PutDuplicateRoleChartOfAccounts(request()->input('parent'), Request::instance()->id),
            'managerial_code' => 'nullable|max:255',
            'group_title' => 'nullable|max:255',
            'referential_title' => 'nullable|max:255',
            'group' => 'nullable|integer',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'plano de contas',
            'parent' => 'parente',
            'code' => 'código',
            'managerial_code' => 'código gerencial',
            'group_title' => 'nome do grupo',
            'referential_title' => 'título referencial',
            'group' => 'grupo',
        ];
    }
}

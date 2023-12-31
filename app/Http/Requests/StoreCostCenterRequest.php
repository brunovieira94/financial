<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\DuplicateRoleCostCenter;

class StoreCostCenterRequest extends FormRequest
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
            'parent' => 'nullable|integer|exists:cost_center,id',
            'code' => new DuplicateRoleCostCenter(request()->input('parent')),
            'group_approval_flow_id' => 'required|integer|exists:group_approval_flow,id',
            'active' => 'boolean'
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'centro de custos',
            'group_approval_flow_id' => 'grupo de aprovação'
        ];
    }
}

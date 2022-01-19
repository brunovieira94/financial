<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReasonToRejectRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:150|unique:reasons_to_reject,title,NULL,id,deleted_at,NULL',
        ];
    }

}

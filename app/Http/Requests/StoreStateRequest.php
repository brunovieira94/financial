<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'country_id' => 'required|integer',
            'title' => 'required|max:150|unique:states,title,NULL,id,deleted_at,NULL',
        ];
    }
}

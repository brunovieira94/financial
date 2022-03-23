<?php

namespace App\Http\Requests;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255|unique:services,title,NULL,id,deleted_at,NULL',
            'chart_of_accounts_id' => 'integer|required',
            'description' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'serviço',
        ];
    }

}

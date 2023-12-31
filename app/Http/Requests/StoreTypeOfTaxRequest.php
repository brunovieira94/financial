<?php

namespace App\Http\Requests;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeOfTaxRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:150|unique:type_of_tax,title,' . $this->id . ',id,deleted_at,NULL',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'tipo de taxa',
        ];
    }

}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttributeTypeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255|unique:attribute_types,title,' . $this->id . ',id,deleted_at,NULL',
            'default' => 'boolean',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'tipos de atributo',
        ];
    }
}

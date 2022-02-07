<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255|unique:provider_categories,title,' . $this->id . ',id,deleted_at,NULL',
            'payment_before_weekends' => 'required|boolean',
        ];
    }
}

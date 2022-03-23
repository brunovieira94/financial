<?php

namespace App\Http\Requests;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementUnitRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|max:255|unique:measurement_units,title,' . $this->id . ',id,deleted_at,NULL',
            'unit' => 'required|max:5|unique:measurement_units,unit,' . $this->id . ',id,deleted_at,NULL',
        ];
    }

    public function attributes()
    {
        return [
            'title' => 'unidade de medida',
        ];
    }

}

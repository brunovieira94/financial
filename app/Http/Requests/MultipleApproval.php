<?php

namespace App\Http\Requests;

use App\Rules\CheckArrayExistsApproval;
use Illuminate\Foundation\Http\FormRequest;

class MultipleApproval extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_requests' => ['required', 'array', new CheckArrayExistsApproval],
            'users' => ['required', 'array', new CheckArrayExistsApproval]
        ];
    }

}

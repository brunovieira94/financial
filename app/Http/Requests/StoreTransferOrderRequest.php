<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            /* 'purchase_order_id' => 'integer|required|exists:purchase_orders,id',
            'order' => 'integer|required',
            'flag' => 'required',
            'users_ids' => 'required|array|exists:users,id', */];
    }
}

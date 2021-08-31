<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentTypeRequest;
use App\Models\PaymentType;

class PaymentTypeController extends Controller
{
    public function index()
    {
        $paymentsTypes = PaymentType::get();
        return response()->json($paymentsTypes);
    }

    public function store(StorePaymentTypeRequest $request)
    {
        $paymentType = new PaymentType;
        $paymentType->title = $request->input('title');
        $paymentType->save();
        return response($paymentType, 201);
    }

    public function update(StorePaymentTypeRequest $request, $id)
    {
        $paymentType = paymentType::findOrFail($id);
        $paymentType->title = $request->input('title');
        $paymentType->save();
        return response()->json($paymentType);
    }

    public function destroy($id)
    {
        $paymentType = paymentType::findOrFail($id)->delete();
        return response()->json($paymentType);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentType;

class PaymentTypeController extends Controller
{
    private $objPaymentType;

    public function index()
    {
        $paymentsTypes = PaymentType::get();
        return response()->json($paymentsTypes);
    }


    public function create()
    {

    }

    public function store(Request $request)
    {
        $paymentType = new PaymentType();
        $paymentType->title = $request->input('title');

        try {
            error_log('try');
            $paymentType->save();
            return response()->json(PaymentType::where('title', '=', $paymentType->title)->first());
        }catch(error){
            return response()->json(['Error' => $error]);
        }

        return $paymentType;
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}

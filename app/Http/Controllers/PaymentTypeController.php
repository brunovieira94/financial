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

    public function store(StorePostRequest $request)
    {
        try {
           $paymentType = PaymentType::firstOrCreate([
           'title' => $request->input('title')
           ]);
           return response()->json($paymentType, 201);
        }catch(\Exception $e){
             return response('', 500);
        }
    }

    public function update(StorePostRequest $request, $id)
    {
     try {
        $paymentType = paymentType::findOrFail($id);
        $paymentType->title = $request->input('title');
        $paymentType->save();
        return response()->json($paymentType);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             return response('',404);

    } catch(\Exception $e){
             return response('',409);
    }
}

    public function destroy($id)
    {
        try {
            $paymentType = paymentType::findOrFail($id)->delete();
            return response()->json($paymentType);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response('',404);

        } catch(\Exception $e){
            return response('',500);
        }
    }
}

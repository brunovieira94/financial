<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentType;
use Validator;

class PaymentTypeController extends Controller
{

    public function index()
    {
        $paymentsTypes = PaymentType::get();
        return response()->json($paymentsTypes);
    }

    public function store(Request $request)
    {
        $rules=array(
            'title' => 'required'
        );
        $validator=Validator::make($request->all(), $rules);
        if($validator->fails()){
            return response($validator->errors(), 400);
        }else {
            try {
                $paymentType = PaymentType::firstOrCreate([
                    'title' => $request->input('title')
                ]);
                return response()->json($paymentType, 201);
            }catch(\Exception $e){
                return response('', 500);
            }

        }


    }

    public function update(Request $request, $id)
    {
        $rules=array(
            'title' => 'required'
        );
        $validator=Validator::make($request->all(), $rules);

        if($validator->fails()){
            return response($validator->errors(), 400);
        }else {
           try {
           $paymentType = paymentType::findOrFail($id);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             return response('',404);

        } catch(\Exception $e){
             return response('',409);
        }
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
            return response('',409);
        }
    }
}

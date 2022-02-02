<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentRequestRequest;
use App\Services\PaymentRequestService as PaymentRequestService;
use App\Http\Requests\PutPaymentRequestRequest;
use App\Imports\PaymentRequestsImport;
use App\Models\PaymentRequest;

class PaymentRequestController extends Controller
{
    private $paymentRequestService;
    private $paymentRequestImport;

    public function __construct(PaymentRequestService $paymentRequestService, PaymentRequestsImport $paymentRequestImport)
    {
        $this->paymentRequestService = $paymentRequestService;
        $this->paymentRequestImport = $paymentRequestImport;
    }

    public function index(Request $request)
    {
        return $this->paymentRequestService->getAllPaymentRequest($request->all());
    }

    public function show($id)
    {
        return $this->paymentRequestService->getPaymentRequest($id);
    }

    public function store(StorePaymentRequestRequest $request)
    {
        $attribute = null;

        if(array_key_exists('bar_code', $request->all())){
            $attribute = 'bar_code';
            $value = $request->bar_code;
        }else if (array_key_exists('invoice_number', $request->all())){
            $attribute = 'invoice_number';
            $value = $request->invoice_number;
        }

        if($attribute != null){
            if (PaymentRequest::with('business')
            ->where($attribute, $value)
            ->whereRelation('business', 'id', '=', $request->business_id)
            ->exists())
            {
                return response()->json([
                    'erro' => 'Este número de nota fiscal/boleto já foi cadastrado para este negócio.'
                ], 409);
            }
        if (PaymentRequest::where($attribute, $value)
            ->exists())
            {
                if ($request->force_registration) {
                    return $this->paymentRequestService->postPaymentRequest($request);
                }
            return response()->json([
                'erro' => 'O número da nota fiscal/boleto já foi cadastrado no sistema em outro negócio, tem certeza que deseja cadastrar mesmo assim?'
            ], 424);
        }

    }
        return $this->paymentRequestService->postPaymentRequest($request);
}

    public function update(PutPaymentRequestRequest $request, $id)
    {
        $attribute = null;
        $business_id = null;

        if(array_key_exists('bar_code', $request->all())){
            $attribute = 'bar_code';
            $value = $request->bar_code;
        }else if (array_key_exists('invoice_number', $request->all())){
            $attribute = 'invoice_number';
            $value = $request->invoice_number;
        }

        if($attribute != null){

            $paymentRequest = PaymentRequest::with('business')->findOrFail($id);
            $columnValidation = '';

            if ($paymentRequest->bar_code == null) {
                $columnValidation = $paymentRequest->invoice_number;
            } else {
                $columnValidation = $paymentRequest->bar_code;
            }
            if ($columnValidation == $value)
            {
                return $this->paymentRequestService->putPaymentRequest($id, $request);
            }

            if(array_key_exists('business_id', $request->all())){
                $business_id = $request->business_id;
            }else{
                $business_id = $paymentRequest->business_id;
            }

            if(PaymentRequest::with('business')
            ->where($attribute, $value)
            ->whereRelation('business', 'id', '=', $business_id)
            ->exists())
            {
                return response()->json([
                    'erro' => 'Este número de nota fiscal/boleto já foi cadastrado para este negócio.'
                ], 409);
            }
            if(PaymentRequest::where($attribute, $value)
            ->exists())
            {
                if ($request->force_registration) {
                    return $this->paymentRequestService->putPaymentRequest($id, $request);
                }
                return response()->json([
                    'erro' => 'Já existe a nota fiscal ou boleto cadastrado no sistema!'
                ], 424);
            }
        }
        return $this->paymentRequestService->putPaymentRequest($id, $request);
    }

    public function destroy($id)
    {
       $paymentRequestService = $this->paymentRequestService->deletePaymentRequest($id);
       return response('');
    }

    public function import()
    {
        $this->paymentRequestImport->import(request()->file('import_file'));
        return response('');
    }
}

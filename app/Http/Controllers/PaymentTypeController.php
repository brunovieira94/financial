<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentTypeRequest;
use App\Services\PaymentTypeService as PaymentTypeService;

class PaymentTypeController extends Controller
{

    private $paymentTypeService;
    public function __construct(PaymentTypeService $paymentTypeService)
    {
        $this->paymentTypeService = $paymentTypeService;
    }

    public function index()
    {
        $paymentType = $this->paymentTypeService->getAllPaymentType();
        return response($paymentType, 200);
    }

    public function show($id)
    {
        $paymentType = $this->paymentTypeService->getPaymentType($id);
        return response($paymentType, 200);
    }

    public function store(StorePaymentTypeRequest $request)
    {
        $paymentType = $this->paymentTypeService->postPaymentType($request->title);
        return response($paymentType, 201);
    }

    public function update(StorePaymentTypeRequest $request, $id)
    {
        $paymentType = $this->paymentTypeService->putPaymentType($id, $request->title);
        return response($paymentType, 200);
    }

    public function destroy($id)
    {
        $paymentType = $this->paymentTypeService->deletePaymentType($id);
        return response('', 200);
    }

}

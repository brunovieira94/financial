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
        return $this->paymentTypeService->getAllPaymentType();
    }

    public function show($id)
    {
        return $this->paymentTypeService->getPaymentType($id);
    }

    public function store(StorePaymentTypeRequest $request)
    {
        return $this->paymentTypeService->postPaymentType($request->all());
    }

    public function update(StorePaymentTypeRequest $request, $id)
    {
        return $this->paymentTypeService->putPaymentType($id, $request->all());
    }

    public function destroy($id)
    {
        $paymentType = $this->paymentTypeService->deletePaymentType($id);
        return response('');
    }

}

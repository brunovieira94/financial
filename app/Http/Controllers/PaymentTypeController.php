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
<<<<<<< HEAD
        return $this->paymentTypeService->getAllPaymentType();
=======
        $paymentType = $this->paymentTypeService->getAllPaymentType();
        return response($paymentType);
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function show($id)
    {
<<<<<<< HEAD
        return $this->paymentTypeService->getPaymentType($id);
=======
        $paymentType = $this->paymentTypeService->getPaymentType($id);
        return response($paymentType);
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function store(StorePaymentTypeRequest $request)
    {
<<<<<<< HEAD
        return $this->paymentTypeService->postPaymentType($request->all());
=======
        $paymentType = $this->paymentTypeService->postPaymentType($request->title);
        return response($paymentType, 201);
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function update(StorePaymentTypeRequest $request, $id)
    {
<<<<<<< HEAD
        return $this->paymentTypeService->putPaymentType($id, $request->all());
=======
        $paymentType = $this->paymentTypeService->putPaymentType($id, $request->title);
        return response($paymentType);
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function destroy($id)
    {
        $paymentType = $this->paymentTypeService->deletePaymentType($id);
        return response('');
    }

}

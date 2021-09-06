<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Services\PaymentMethodService as PaymentMethodService;

class PaymentMethodController extends Controller
{

    private $paymentMethodService;

    public function __construct(PaymentMethodService $paymentMethodService)
    {
        $this->paymentMethodService = $paymentMethodService;
    }

    public function index()
    {
        return $this->paymentMethodService->getAllPaymentMethod();
    }

    public function show($id)
    {
        return $this->paymentMethodService->getPaymentMethod($id);
    }

    public function store(StorePaymentMethodRequest $request)
    {
        $paymentMethod = $this->paymentMethodService->postPaymentMethod($request->all());
        return response($paymentMethod, 201);
    }

    public function update(StorePaymentMethodRequest $request, $id)
    {
        return $this->paymentMethodService->putPaymentMethod($id, $request->all());
    }

    public function destroy($id)
    {
        $paymentMethod = $this->paymentMethodService->deletePaymentMethod($id);
        return response('');
    }
}

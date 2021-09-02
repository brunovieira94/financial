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
        $paymentMethod = $this->paymentMethodService->getAllPaymentMethod();
        return response($paymentMethod);
    }

    public function show($id)
    {
        $paymentMethod = $this->paymentMethodService->getPaymentMethod($id);
        return response($paymentMethod);
    }

    public function store(StorePaymentMethodRequest $request)
    {
        $paymentMethod = $this->paymentMethodService->postPaymentMethod($request->title);
        return response($paymentMethod, 201);
    }

    public function update(StorePaymentMethodRequest $request, $id)
    {
        $paymentMethod = $this->paymentMethodService->putPaymentMethod($id, $request->title);
        return response($paymentMethod);
    }

    public function destroy($id)
    {
        $paymentMethod = $this->paymentMethodService->deletePaymentMethod($id);
        return response('');
    }
}
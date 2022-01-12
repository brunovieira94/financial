<?php

namespace App\Http\Controllers;

use App\Imports\PaymentMethodsImport;
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

    public function index(Request $request)
    {
        return $this->paymentMethodService->getAllPaymentMethod($request->all());
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

    public function import()
    {
        (new PaymentMethodsImport)->import(request()->file('import_file'));
        return response('');
    }
}

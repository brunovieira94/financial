<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentRequestRequest;
use App\Services\PaymentRequestService as PaymentRequestService;
use App\Http\Requests\PutPaymentRequestRequest;
use App\Imports\PaymentRequestsImport;

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
        return $this->paymentRequestService->postPaymentRequest($request);
    }

    public function update(PutPaymentRequestRequest $request, $id)
    {
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

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BillingPaymentService as BillingPaymentService;

class BillingPaymentController extends Controller
{
    private $billingPaymentService;

    public function __construct(BillingPaymentService $billingPaymentService)
    {
        $this->billingPaymentService = $billingPaymentService;
    }

    public function index(Request $request)
    {
        return $this->billingPaymentService->getAllBillingPayment($request->all());
    }

    public function show($id)
    {
        return $this->billingPaymentService->getBillingPayment($id);
    }

    public function destroy($id)
    {
        return $this->billingPaymentService->deleteBillingPayment($id);
    }
}

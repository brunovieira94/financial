<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BillingService as BillingService;
use App\Http\Requests\StoreBillingRequest;
use App\Http\Requests\PutBillingRequest;

class BillingController extends Controller
{
    private $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function index(Request $request)
    {
        return $this->billingService->getAllBilling($request->all());
    }

    public function show($id)
    {
        return $this->billingService->getBilling($id);
    }

    public function store(StoreBillingRequest $request)
    {
        return $this->billingService->postBilling($request->all());
    }

    public function update(PutBillingRequest $request, $id)
    {
        return $this->billingService->putBilling($id, $request->all());
    }

    public function destroy($id)
    {
        $this->billingService->deleteBilling($id);
        return response('');
    }
}

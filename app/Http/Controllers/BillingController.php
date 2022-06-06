<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BillingService as BillingService;
use App\Services\CangoorooService as CangoorooService;
use App\Http\Requests\StoreBillingRequest;
use App\Http\Requests\PutBillingRequest;

class BillingController extends Controller
{
    private $billingService;
    private $cangoorooService;

    public function __construct(BillingService $billingService, CangoorooService $cangoorooService)
    {
        $this->billingService = $billingService;
        $this->cangoorooService = $cangoorooService;
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

    public function getCangoorooData(Request $request)
    {
        $cangooroo = $this->cangoorooService->updateCangoorooData($request->booking_id,$request->reserve);
        if(is_array($cangooroo) && array_key_exists('error', $cangooroo)){
            return response()->json([
                'error' => $cangooroo['error'],
            ], 422);
        }
        return $cangooroo;
    }
}

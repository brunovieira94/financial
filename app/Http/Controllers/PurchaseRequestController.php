<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePurchaseRequestRequest;
use App\Http\Requests\PutPurchaseRequestRequest;
use App\Services\PurchaseRequestService as PurchaseRequestService;

class PurchaseRequestController extends Controller
{

    private $purchaseRequestService;

    public function __construct(PurchaseRequestService $purchaseRequestService)
    {
        $this->purchaseRequestService = $purchaseRequestService;
    }

    public function index(Request $request)
    {
        return $this->purchaseRequestService->getAllPurchaseRequest($request->all());
    }

    public function show($id)
    {
        return $this->purchaseRequestService->getPurchaseRequest($id);
    }

    public function store(StorePurchaseRequestRequest $request)
    {
        return $this->purchaseRequestService->postPurchaseRequest($request->all(), $request);
    }

    public function update(PutPurchaseRequestRequest $request, $id)
    {
        return $this->purchaseRequestService->putPurchaseRequest($id, $request->all(), $request);
    }

    public function destroy($id)
    {
        $purchaseRequest = $this->purchaseRequestService->deletePurchaseRequest($id);
        return response('');
    }

}

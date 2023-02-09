<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferOrderRequest;
use App\Services\TransferOrderService;
use Illuminate\Http\Request;

class TransferOrderController extends Controller
{
    private $transferOrderService;

    public function __construct(TransferOrderService $transferOrderService)
    {
        $this->transferOrderService = $transferOrderService;
    }

    public function store(StoreTransferOrderRequest $request)
    {
        return $this->transferOrderService->postUserApprover($request->all());
    }

    public function getUserApprover($id)
    {
        return $this->transferOrderService->getUserApprover($id);
    }

    public function cancelTransferOrder($id)
    {
        return $this->transferOrderService->cancelTransferOrder($id);
    }
}

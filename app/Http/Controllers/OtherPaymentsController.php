<?php

namespace App\Http\Controllers;

use App\Http\Requests\OtherPaymentsRequest;
use App\Services\OtherPaymentsService;
use Illuminate\Http\Request;

class OtherPaymentsController extends Controller
{
    private $otherPaymentsService;

    public function __construct(OtherPaymentsService $otherPaymentsService)
    {
        $this->otherPaymentsService = $otherPaymentsService;
    }

    public function storePayment(OtherPaymentsRequest $request)
    {
        return $this->otherPaymentsService->storePayment($request);
    }

    public function approvedPaymentRequestsResolveStatus(Request $request)
    {
        $this->otherPaymentsService->checkOverPaymentRequestsStatus();
        return response()->json(['success' => 'Sucesso']);
    }
}

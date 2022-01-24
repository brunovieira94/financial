<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;

class ReportController extends Controller
{

    private $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function duePaymentRequest(Request $request)
    {
        return $this->reportService->getAllDuePaymentRequest($request->all());
    }

    public function approvedPaymentRequest(Request $request)
    {
        return $this->reportService->getAllApprovedPaymentRequest($request->all());
    }

    public function disapprovedPaymentRequest(Request $request)
    {
        return $this->reportService->getAllDisapprovedPaymentRequest($request->all());
    }

    public function paymentRequestsDeleted(Request $request)
    {
        return $this->reportService->getAllPaymentRequestsDeleted($request->all());
    }

    public function billsToPay(Request $request)
    {
        return $this->reportService->getBillsToPay($request->all());
    }
}

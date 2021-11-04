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

    public function dueBills(Request $request)
    {
        return $this->reportService->getAllDueBills($request->all());
    }

    public function approvedBills(Request $request)
    {
        return $this->reportService->getAllApprovedBills($request->all());
    }
}

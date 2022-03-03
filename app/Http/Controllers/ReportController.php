<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Exports\AllDuePaymentRequestExport;
use App\Exports\AllApprovedPaymentRequestExport;
use App\Exports\AllDisapprovedPaymentRequestExport;
use App\Exports\AllPaymentRequestsDeletedExport;
use App\Exports\AllGeneratedCNABPaymentRequestExport;
use App\Exports\BillsToPayExport;
use App\Exports\AllPaymentRequestPaidExport;
use App\Exports\AllPaymentRequestFinishedExport;

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

    public function duePaymentRequestExport(Request $request)
    {
        return (new AllDuePaymentRequestExport($request->all()))->download('contasVencidas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    }

    public function approvedPaymentRequest(Request $request)
    {
        $request->validate([
            'payment_type' => 'required|integer',
        ]);
        return $this->reportService->getAllApprovedPaymentRequest($request->all());
    }

    public function approvedPaymentRequestExport(Request $request)
    {
        return (new AllApprovedPaymentRequestExport($request->all()))->download('contasAprovadas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    }

    public function disapprovedPaymentRequest(Request $request)
    {
        return $this->reportService->getAllDisapprovedPaymentRequest($request->all());
    }

    public function disapprovedPaymentRequestExport(Request $request)
    {
        return (new AllDisapprovedPaymentRequestExport($request->all()))->download('contasRejeitadas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    }

    public function paymentRequestsDeleted(Request $request)
    {
        return $this->reportService->getAllPaymentRequestsDeleted($request->all());
    }

    public function paymentRequestsDeletedExport(Request $request)
    {
        return (new AllPaymentRequestsDeletedExport($request->all()))->download('contasDeletadas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    }

    public function generatedCNABPaymentRequestCNAB(Request $request)
    {
        return $this->reportService->getAllGeneratedCNABPaymentRequest($request->all());
    }

    public function generatedCNABPaymentRequestCNABExport(Request $request)
    {
        return (new AllGeneratedCNABPaymentRequestExport($request->all()))->download('CNABgerados.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    }

    public function billsToPay(Request $request)
    {
        return $this->reportService->getBillsToPay($request->all());
    }

    public function billsToPayExport(Request $request)
    {
        return (new BillsToPayExport($request->all()))->download('contasAPagar.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    }

    public function paymentRequestPaid(Request $request)
    {
        return $this->reportService->getAllPaymentRequestPaid($request->all());
    }

    public function paymentRequestPaidExport(Request $request)
    {
        return (new AllPaymentRequestPaidExport($request->all()))->download('contasPagas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    }

    public function paymentRequestFinished(Request $request)
    {
        return $this->reportService->getAllPaymentRequestFinished($request->all());
    }

    public function paymentRequestFinishedExport(Request $request)
    {
        return (new AllPaymentRequestFinishedExport($request->all()))->download('contasFinalizadas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
    }
}

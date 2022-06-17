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

    public function dueInstallment(Request $request)
    {
        return $this->reportService->getAllDueInstallment($request->all());
    }

    public function duePaymentRequestExport(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new AllDuePaymentRequestExport($request->all()))->download('contasVencidas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new AllDuePaymentRequestExport($request->all()))->download('contasVencidas.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function approvedPaymentRequest(Request $request)
    {
        return $this->reportService->getAllApprovedPaymentRequest($request->all());
    }

    public function approvedPaymentRequestExport(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new AllApprovedPaymentRequestExport($request->all()))->download('contasAprovadas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new AllApprovedPaymentRequestExport($request->all()))->download('contasAprovadas.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function approvedInstallment(Request $request)
    {
        return $this->reportService->getAllApprovedInstallment($request->all());
    }

    public function disapprovedPaymentRequest(Request $request)
    {
        return $this->reportService->getAllDisapprovedPaymentRequest($request->all());
    }

    public function disapprovedPaymentRequestExport(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new AllDisapprovedPaymentRequestExport($request->all()))->download('contasRejeitadas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new AllDisapprovedPaymentRequestExport($request->all()))->download('contasRejeitadas.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function paymentRequestsDeleted(Request $request)
    {
        return $this->reportService->getAllPaymentRequestsDeleted($request->all());
    }

    public function paymentRequestsDeletedExport(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new AllPaymentRequestsDeletedExport($request->all()))->download('contasDeletadas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new AllPaymentRequestsDeletedExport($request->all()))->download('contasDeletadas.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function generatedCNABPaymentRequestCNAB(Request $request)
    {
        return $this->reportService->getAllGeneratedCNABPaymentRequest($request->all());
    }

    public function generatedCNABPaymentRequestCNABExport(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {

                return (new AllGeneratedCNABPaymentRequestExport($request->all()))->download('CNABgerados.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new AllGeneratedCNABPaymentRequestExport($request->all()))->download('CNABgerados.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function billsToPay(Request $request)
    {
        return $this->reportService->getBillsToPay($request->all());
    }

    public function billsToPayExport(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new BillsToPayExport($request->all()))->download('contasAPagar.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }

        return (new BillsToPayExport($request->all()))->download('contasAPagar.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function installmentsPayable(Request $request)
    {
        return $this->reportService->getInstallmentsPayable($request->all());
    }

    public function paymentRequestPaid(Request $request)
    {
        return $this->reportService->getAllPaymentRequestPaid($request->all());
    }

    public function paymentRequestPaidExport(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new AllPaymentRequestPaidExport($request->all()))->download('contasPagas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new AllPaymentRequestPaidExport($request->all()))->download('contasPagas.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function paymentRequestFinished(Request $request)
    {
        return $this->reportService->getAllPaymentRequestFinished($request->all());
    }

    public function paymentRequestFinishedExport(Request $request)
    {
        if (array_key_exists('exportFormat', $request->all())) {
            if ($request->all()['exportFormat'] == 'csv') {
                return (new AllPaymentRequestFinishedExport($request->all()))->download('contasFinalizadas.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new AllPaymentRequestFinishedExport($request->all()))->download('contasFinalizadas.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function approvedPurchaseOrder(Request $request)
    {
        return $this->reportService->getAllApprovedPurchaseOrder($request->all());
    }

    public function getAllCnabGenerate(Request $request)
    {
        return $this->reportService->getAllCnabGenerate($request->all());
    }

    public function getCnabGenerate(Request $request, $id)
    {
        return $this->reportService->getCnabGenerate($request->all(), $id);
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\AllApprovedInstallment;
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
use App\Exports\CnabGeneratedExport;
use App\Exports\DueInstallmentsExport;
use App\Exports\InstallmentsPayableExport;
use App\Exports\UserApprovalsReportExport;
use App\Exports\Utils as UtilsExport;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Models\Export;
use DB;
use App\Exports\AllApprovedInstallmentExportForPaidImport;

class ReportController extends Controller
{

    private $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
        ini_set('memory_limit', '1024M');
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
        $exportFile = UtilsExport::exportFile($request->all(), 'contasVencidas');

        (new AllDuePaymentRequestExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function dueInstallmentsExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'parcelasVencidas');

        (new DueInstallmentsExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function approvedPaymentRequest(Request $request)
    {
        if (array_key_exists('form_payment_id', $request->all())) {
            if (!array_key_exists('company_id', $request->all())) {
                return response()->json([
                    'error' => 'A empresa não foi informada'
                ], 422);
            }
        }
        return $this->reportService->getAllApprovedPaymentRequest($request->all());
    }

    public function approvedPaymentRequestExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'contasAprovadas');

        (new AllApprovedPaymentRequestExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function approvedInstallment(Request $request)
    {
        return $this->reportService->getAllApprovedInstallment($request->all());
    }

    public function approvedInstallmentExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'parcelasAprovadas');

        (new AllApprovedInstallment($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function disapprovedPaymentRequest(Request $request)
    {
        return $this->reportService->getAllDisapprovedPaymentRequest($request->all());
    }

    public function disapprovedPaymentRequestExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'contasReprovadas');

        (new AllDisapprovedPaymentRequestExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function paymentRequestsDeleted(Request $request)
    {
        return $this->reportService->getAllPaymentRequestsDeleted($request->all());
    }

    public function paymentRequestsDeletedExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'contasDeletadas');

        (new AllPaymentRequestsDeletedExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function generatedCNABPaymentRequestCNAB(Request $request)
    {
        return $this->reportService->getAllGeneratedCNABPaymentRequest($request->all());
    }

    public function generatedCNABPaymentRequestCNABExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'CnabGerados');

        (new AllGeneratedCNABPaymentRequestExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function billsToPay(Request $request)
    {
        return $this->reportService->getBillsToPay($request->all());
    }

    public function billsToPayExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'contasAPagar');

        (new BillsToPayExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function installmentsPayable(Request $request)
    {
        return $this->reportService->getInstallmentsPayable($request->all());
    }

    public function installmentsPayableExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'parcelasAPagar');

        (new InstallmentsPayableExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function paymentRequestPaid(Request $request)
    {
        return $this->reportService->getAllPaymentRequestPaid($request->all());
    }

    public function paymentRequestPaidExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'contasPagas');

        (new AllPaymentRequestPaidExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function paymentRequestFinished(Request $request)
    {
        return $this->reportService->getAllPaymentRequestFinished($request->all());
    }

    public function paymentRequestFinishedExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'contasFinalizadas');

        (new AllPaymentRequestFinishedExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function approvedPurchaseOrder(Request $request)
    {
        return $this->reportService->getAllApprovedPurchaseOrder($request->all());
    }

    public function approvedPurchaseOrderForIntegration(Request $request)
    {
        return $this->reportService->getAllApprovedPurchaseOrderForIntegration($request->all());
    }

    public function getAllCnabGenerate(Request $request)
    {
        return $this->reportService->getAllCnabGenerate($request->all());
    }

    public function getCnabGenerate(Request $request, $id)
    {
        return $this->reportService->getCnabGenerate($request->all(), $id);
    }

    public function userApprovalsReport(Request $request)
    {
        return $this->reportService->getUserApprovalsReport($request->all());
    }

    public function userApprovalsReportExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'usuarioAprovacoes');

        (new UserApprovalsReportExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function getCnabGenerateExport(Request $request, $id)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'cnabGerado');

        (new CnabGeneratedExport($request->all(), $id, $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function getReport()
    {
        return Export::where('user_id', auth()->user()->id)->where('updated_at', '>', now()->subDays(1))->orderBy('id', 'DESC')->get();
    }

    public function getReportById(Request $request, $id)
    {
        return Export::findOrFail($id);
    }
}

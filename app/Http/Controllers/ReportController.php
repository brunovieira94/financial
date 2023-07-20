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
use App\Exports\AllApprovedInstallmentExportForPaidImport;
use App\Exports\PaymentRequestExport;
use App\Exports\PaymentRequestExportQueue;
use App\Exports\PaymentRequestHasInstalmentExport;
use App\Exports\PaymentRequestHasInstalmentExportQueue;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Services\Utils;
use DB;

class ReportController extends Controller
{

    private $reportService;
    private $paymentRequest;
    private $installment;

    public function __construct(ReportService $reportService, PaymentRequestClean $paymentRequest, PaymentRequestHasInstallmentsClean $installment)
    {
        $this->reportService = $reportService;
        $this->paymentRequest = $paymentRequest;
        $this->installment = $installment;
        ini_set('memory_limit', '2048M');
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

        $paymentRequest = $this->paymentRequest::query();
        $requestInfo = $request->all();
        $paymentRequest = $paymentRequest->with(UtilsExport::withModelDefaultExport('payment-request'));
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $request->all());
        $paymentRequest = $paymentRequest->whereHas('installments', function ($query) use ($requestInfo) {
            if (array_key_exists('from', $requestInfo)) {
                $query = $query->where('extension_date', '>=', $requestInfo['from']);
            }
            if (array_key_exists('to', $requestInfo)) {
                $query = $query->where('extension_date', '<=', $requestInfo['to']);
            }
            if (!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)) {
                $query = $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
            }
        });

        if ($paymentRequest->count() < env('LIMIT_EXPORT_PROCESS', 2500)) {
            (new PaymentRequestExport($exportFile['nameFile'], $paymentRequest, $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        } else {
            UtilsExport::convertExportFormat($exportFile);
            $exportFileDB = Export::findOrFail($exportFile['id']);
            (new PaymentRequestExportQueue($paymentRequest->get()))
                ->queue($exportFileDB->path, 's3')
                ->allOnQueue('default')
                ->chain([
                    new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
                ]);
        }

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function dueInstallmentsExport(Request $request)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'parcelasVencidas');

        $requestInfo = $request->all();
        $installment = $this->installment::query();
        $installment = $installment->with(UtilsExport::withModelDefaultExport('payment-request-installments'));
        $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
            if (array_key_exists('from', $requestInfo)) {
                $query = $query->where('extension_date', '>=', $requestInfo['from']);
            }
            if (array_key_exists('to', $requestInfo)) {
                $query = $query->where('extension_date', '<=', $requestInfo['to']);
            }
            if (!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)) {
                $query = $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
            }
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        $installment = Utils::baseFilterReportsInstallment($installment, $requestInfo);

        if ($installment->count() < env('LIMIT_EXPORT_PROCESS', 2500)) {
            (new PaymentRequestHasInstalmentExport($exportFile['nameFile'], $installment, $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        } else {
            UtilsExport::convertExportFormat($exportFile);
            $exportFileDB = Export::findOrFail($exportFile['id']);
            (new PaymentRequestHasInstalmentExportQueue($installment->get()))
                ->queue($exportFileDB->path, 's3')
                ->allOnQueue('default')
                ->chain([
                    new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
                ]);
        }

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
        $paymentRequest = $this->paymentRequest::query();
        $paymentRequest = $paymentRequest->with(UtilsExport::withModelDefaultExport('payment-request'));
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $request->all());
        $paymentRequest = $paymentRequest->whereHas('approval', function ($query) {
            $query = $query->where('status', 1);
        });

        if ($paymentRequest->count() < env('LIMIT_EXPORT_PROCESS', 2500)) {
            (new PaymentRequestExport($exportFile['nameFile'], $paymentRequest, $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        } else {
            UtilsExport::convertExportFormat($exportFile);
            $exportFileDB = Export::findOrFail($exportFile['id']);
            (new PaymentRequestExportQueue($paymentRequest->get()))
                ->queue($exportFileDB->path, 's3')
                ->allOnQueue('default')
                ->chain([
                    new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
                ]);
        }

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
        $requestInfo = $request->all();
        $installment = $this->installment::query();
        $installment = $installment->with(UtilsExport::withModelDefaultExport('payment-request-installments'));
        $installment = $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('status', 1);
            });
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        $installment = Utils::baseFilterReportsInstallment($installment, $requestInfo);

        if (array_key_exists('isForImportPayment', $request->all()) && $request->all()['isForImportPayment'] == true) {
            (new AllApprovedInstallmentExportForPaidImport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', \Maatwebsite\Excel\Excel::XLSX)->chain([
                new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
            ]);
        } else {
            if ($installment->count() < env('LIMIT_EXPORT_PROCESS', 2500)) {
                (new PaymentRequestHasInstalmentExport($exportFile['nameFile'], $installment, $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
            } else {
                UtilsExport::convertExportFormat($exportFile);
                $exportFileDB = Export::findOrFail($exportFile['id']);
                (new PaymentRequestHasInstalmentExportQueue($installment->get()))
                    ->queue($exportFileDB->path, 's3')
                    ->allOnQueue('default')
                    ->chain([
                        new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
                    ]);
            }
        }

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function approvedInstallmentForImportPaymentExport(Request $request)
    {
        $requestInfo = $request->all();
        $exportFile = UtilsExport::exportFile($request->all(), 'parcelasAprovadasImport');

        $headers = null;
        $format = \Maatwebsite\Excel\Excel::XLSX;
        $ext = '.xlsx';

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

        $paymentRequest = $this->paymentRequest::query();
        $paymentRequest = $paymentRequest->with(UtilsExport::withModelDefaultExport('payment-request'));
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $request->all());
        $paymentRequest = $paymentRequest->withTrashed();
        $paymentRequest = $paymentRequest->whereHas('approval', function ($query) {
            $query = $query->where('status', 3);
        });

        if ($paymentRequest->count() < env('LIMIT_EXPORT_PROCESS', 2500)) {
            (new PaymentRequestExport($exportFile['nameFile'], $paymentRequest, $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        } else {
            UtilsExport::convertExportFormat($exportFile);
            $exportFileDB = Export::findOrFail($exportFile['id']);
            (new PaymentRequestExportQueue($paymentRequest->get()))
                ->queue($exportFileDB->path, 's3')
                ->allOnQueue('default')
                ->chain([
                    new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
                ]);
        }

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
        UtilsExport::convertExportFormat($exportFile);
        $exportFileDB = Export::findOrFail($exportFile['id']);

        (new AllGeneratedCNABPaymentRequestExport($request->all(), $exportFile['nameFile']))->queue($exportFileDB->path, 's3')
            ->allOnQueue('default')
            ->chain([
                new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
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

        $paymentRequest = $this->paymentRequest::query();
        $paymentRequest = $paymentRequest->with(UtilsExport::withModelDefaultExport('payment-request'));
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $request->all());

        if ($paymentRequest->count() < env('LIMIT_EXPORT_PROCESS', 2500)) {
            (new PaymentRequestExport($exportFile['nameFile'], $paymentRequest, $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        } else {
            UtilsExport::convertExportFormat($exportFile);
            $exportFileDB = Export::findOrFail($exportFile['id']);
            (new PaymentRequestExportQueue($paymentRequest->get()))
                ->queue($exportFileDB->path, 's3')
                ->allOnQueue('default')
                ->chain([
                    new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
                ]);
        }

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
        $requestInfo = $request->all();
        $installment = $this->installment::query();
        $installment = $installment->with(UtilsExport::withModelDefaultExport('payment-request-installments'));
        $installment = $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        $installment = Utils::baseFilterReportsInstallment($installment, $requestInfo);

        if ($installment->count() < env('LIMIT_EXPORT_PROCESS', 2500)) {
            (new PaymentRequestHasInstalmentExport($exportFile['nameFile'], $installment, $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        } else {
            UtilsExport::convertExportFormat($exportFile);
            $exportFileDB = Export::findOrFail($exportFile['id']);
            (new PaymentRequestHasInstalmentExportQueue($installment->get()))
                ->queue($exportFileDB->path, 's3')
                ->allOnQueue('default')
                ->chain([
                    new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
                ]);
        }

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

        $paymentRequest = $this->paymentRequest::query();
        $paymentRequest = $paymentRequest->with(UtilsExport::withModelDefaultExport('payment-request'));
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $request->all());
        $paymentRequest = $paymentRequest->whereHas('approval', function ($query) {
            $query = $query->where('status', 4);
        });

        if ($paymentRequest->count() < env('LIMIT_EXPORT_PROCESS', 2500)) {
            (new PaymentRequestExport($exportFile['nameFile'], $paymentRequest, $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        } else {
            UtilsExport::convertExportFormat($exportFile);
            $exportFileDB = Export::findOrFail($exportFile['id']);
            (new PaymentRequestExportQueue($paymentRequest->get()))
                ->queue($exportFileDB->path, 's3')
                ->allOnQueue('default')
                ->chain([
                    new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
                ]);
        }

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
        UtilsExport::convertExportFormat($exportFile);
        $exportFileDB = Export::findOrFail($exportFile['id']);

        (new UserApprovalsReportExport($request->all(), $exportFile['nameFile']))->queue($exportFileDB->path, 's3')
            ->allOnQueue('default')
            ->chain([
                new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
            ]);

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function getCnabGenerateExport(Request $request, $id)
    {
        $exportFile = UtilsExport::exportFile($request->all(), 'cnabGerado');
        UtilsExport::convertExportFormat($exportFile);
        $exportFileDB = Export::findOrFail($exportFile['id']);

        (new CnabGeneratedExport($request->all(), $id, $exportFile['nameFile']))->queue($exportFileDB->path, 's3')
            ->allOnQueue('default')
            ->chain([
                new NotifyUserOfCompletedExport($exportFileDB->path, $exportFileDB),
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

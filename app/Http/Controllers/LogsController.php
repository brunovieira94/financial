<?php

namespace App\Http\Controllers;

use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\PaymentRequestClean;
use Illuminate\Http\Request;
use App\Services\LogsService as LogsService;
use DB;
use Response;

class LogsController extends Controller
{

    private $logsService;

    public function __construct(LogsService $logsService)
    {
        $this->logsService = $logsService;
    }

    public function index(Request $request)
    {
        return $this->logsService->getAllLogs($request->all());
    }

    public function getLogs(Request $request, $log_name, $subject_id)
    {
        return $this->logsService->getLogs($log_name, $subject_id, $request->all());
    }

    public function getPaymentRequestLogs(Request $request, $id)
    {
        return $this->logsService->getPaymentRequestLogs($id, $request->all());
    }

    public function getPurchaseOrderLogs(Request $request, $id)
    {
        return $this->logsService->getPurchaseOrderLogs($id, $request->all());
    }

    public function getAccountsPayableApprovalFlowLog(Request $request, $id)
    {
        return $this->logsService->getAccountsPayableApprovalFlowLog($id, $request->all());
    }

    public function getLogPaymentRequestUpdate(Request $request, $id)
    {
        return $this->logsService->getLogPaymentRequestUpdate($id, $request->all());
    }

    public function approvalManualPaymentRequest(Request $request, $id)
    {
        if (DB::table('payment_requests_installments')->where('id', $id)->exists()) {
            $installment = DB::table('payment_requests_installments')->where('id', $id)->first();

            DB::table('accounts_payable_approval_flows')
                ->where('payment_request_id', $installment->payment_request_id)
                ->update(['status' => 1]);

            DB::table('payment_requests_installments')
                ->where('id', $installment->id)
                ->update(['status' => 0]);

            return Response([
                'success' =>   'Conta aprovada'
            ]);
        } else {
            return Response([
                'error' =>   'Conta não encontrada'
            ]);
        }
    }
}

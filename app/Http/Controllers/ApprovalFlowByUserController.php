<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApprovalFlowByUserService;
use App\Http\Requests\PutAccountsPayableApprovalFlowRequest;
use App\Exports\AccountsPayableApprovalFlowExport;
use App\Exports\PaymentRequestExport;
use App\Exports\PaymentRequestExportQueue;
use App\Exports\Utils;
use App\Http\Requests\MultipleApproval;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Models\PaymentRequestClean;
use App\Exports\Utils as UtilsExport;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\ApprovalFlow;
use App\Models\UserHasPaymentRequest;
use App\Services\Utils as ServicesUtils;

class ApprovalFlowByUserController extends Controller
{
    private $accountsPayableApprovalFlowService;
    private $paymentRequest;

    public function __construct(ApprovalFlowByUserService $accountsPayableApprovalFlowService, PaymentRequestClean $paymentRequest)
    {
        $this->accountsPayableApprovalFlowService = $accountsPayableApprovalFlowService;
        $this->paymentRequest = $paymentRequest;
    }

    public function accountsApproveUser(Request $request)
    {
        return $this->accountsPayableApprovalFlowService->getAllAccountsForApproval($request->all());
    }

    public function accountsApproveUserExport(Request $request)
    {
        $exportFile = Utils::exportFile($request->all(), 'contasAAprovar');

        $paymentRequest = $this->paymentRequest::query();
        $paymentRequest = $paymentRequest->with(UtilsExport::withModelDefaultExport('payment-request'));

        $requestInfo = $request->all();
        $approvalFlowUserOrder = ApprovalFlow::where('role_id', auth()->user()->role_id)->get(['order', 'group_approval_flow_id']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $paymentRequest = PaymentRequestClean::with(['approval']);
        $paymentRequest = ServicesUtils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);

        $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
            $arrayStatus = ServicesUtils::statusApprovalFlowRequest($requestInfo);
            $query->whereIn('status', $arrayStatus)
                ->where('deleted_at', '=', null);
        });
        $idsPaymentRequestOrder = [];
        foreach ($approvalFlowUserOrder as $approvalOrder) {
            $accountApprovalFlow = AccountsPayableApprovalFlowClean::where('order', $approvalOrder['order'])->with('payment_request');
            $accountApprovalFlow = $accountApprovalFlow->whereHas('payment_request', function ($query) use ($approvalOrder) {
                $query->where('group_approval_flow_id', $approvalOrder['group_approval_flow_id']);
            })->get('payment_request_id');
            $idsPaymentRequestOrder = array_merge($idsPaymentRequestOrder, $accountApprovalFlow->pluck('payment_request_id')->toArray());
        }
        $paymentRequest = $paymentRequest->whereIn('id', $idsPaymentRequestOrder);
        $multiplePaymentRequest = UserHasPaymentRequest::where('user_id', auth()->user()->id)->where('status', 0)->get('payment_request_id');
        //$paymentRequest = $paymentRequest->orWhere(function ($query) use ($multiplePaymentRequest, $requestInfo) {
        $ids = $multiplePaymentRequest->pluck('payment_request_id')->toArray();
        $paymentRequestMultiple = PaymentRequestClean::withoutGlobalScopes()->whereIn('id', $ids);
        $paymentRequestMultiple = ServicesUtils::baseFilterReportsPaymentRequest($paymentRequestMultiple, $requestInfo);
        $paymentRequestMultiple->get('id');
        $ids = $paymentRequestMultiple->pluck('id')->toArray();
        //union ids payment request
        $paymentRequestIDs = $paymentRequest->get('id');
        $paymentRequestIDs = $paymentRequest->pluck('id')->toArray();
        $ids = array_merge($ids, $paymentRequestIDs);
        $paymentRequest = PaymentRequestClean::withoutGlobalScopes()->whereIn('id', $ids)->with(UtilsExport::withModelDefaultExport('payment-request'));
        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'id';

        if ($paymentRequest->count() < env('LIMIT_EXPORT_PROCESS', 2500)) {
            (new PaymentRequestExport($exportFile['nameFile'], $paymentRequest, $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        } else {
            (new PaymentRequestExportQueue($exportFile['nameFile'], $paymentRequest->get(), $exportFile))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV);
        }

        return response()->json([
            'sucess' => $exportFile['export']->id
        ], 200);
    }

    public function approveAccount($id, Request $request)
    {
        return $this->accountsPayableApprovalFlowService->approveAccount($id, $request->all());
    }

    public function approveManyAccounts(Request $request)
    {
        return $this->accountsPayableApprovalFlowService->approveManyAccounts($request->all());
    }

    public function reproveAccount($id, PutAccountsPayableApprovalFlowRequest $request)
    {
        return $this->accountsPayableApprovalFlowService->reproveAccount($id, $request->all());
    }

    public function cancelAccount($id, PutAccountsPayableApprovalFlowRequest $request)
    {
        $this->accountsPayableApprovalFlowService->cancelAccount($id, $request);
        return response('Conta cancelada');
    }

    public function multipleApproval(MultipleApproval $request)
    {
        return $this->accountsPayableApprovalFlowService->multipleApproval($request->all());
    }

    public function transferApproval(Request $request)
    {
        return $this->accountsPayableApprovalFlowService->transferApproval($request->all());
    }
}

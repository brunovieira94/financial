<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApprovalFlowByUserService;
use App\Http\Requests\PutAccountsPayableApprovalFlowRequest;
use App\Exports\AccountsPayableApprovalFlowExport;
use App\Exports\Utils;
use App\Http\Requests\MultipleApproval;
use App\Jobs\NotifyUserOfCompletedExport;

class ApprovalFlowByUserController extends Controller
{
    private $accountsPayableApprovalFlowService;

    public function __construct(ApprovalFlowByUserService $accountsPayableApprovalFlowService)
    {
        $this->accountsPayableApprovalFlowService = $accountsPayableApprovalFlowService;
    }

    public function accountsApproveUser(Request $request)
    {
        return $this->accountsPayableApprovalFlowService->getAllAccountsForApproval($request->all());
    }

    public function accountsApproveUserExport(Request $request)
    {
        $exportFile = Utils::exportFile($request->all(), 'contasAAprovar');

        (new AccountsPayableApprovalFlowExport($request->all(), $exportFile['nameFile']))->store($exportFile['path'], 's3', $exportFile['extension'] == '.xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV)->chain([
            new NotifyUserOfCompletedExport($exportFile['path'], $exportFile['export']),
        ]);

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

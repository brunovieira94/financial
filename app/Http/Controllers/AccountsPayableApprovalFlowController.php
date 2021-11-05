<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AccountsPayableApprovalFlowService;
use App\Http\Requests\PutAccountsPayableApprovalFlowRequest;

class AccountsPayableApprovalFlowController extends Controller
{

    private $accountsPayableApprovalFlowService;

    public function __construct(AccountsPayableApprovalFlowService $accountsPayableApprovalFlowService)
    {
        $this->accountsPayableApprovalFlowService = $accountsPayableApprovalFlowService;
    }

    public function accountsApproveUser(Request $request)
    {
        return $this->accountsPayableApprovalFlowService->getAllAccountsForApproval($request->all());
    }

    public function approveAccount($id)
    {
        $this->accountsPayableApprovalFlowService->approveAccount($id);
        return response('Conta aprovada');
    }

    public function reproveAccount($id, PutAccountsPayableApprovalFlowRequest $request)
    {
        $this->accountsPayableApprovalFlowService->reproveAccount($id, $request);
        return response('Conta reprovada');
    }

    public function cancelAccount($id, PutAccountsPayableApprovalFlowRequest $request)
    {
        $this->accountsPayableApprovalFlowService->cancelAccount($id, $request);
        return response('Conta cancelada');
    }

}

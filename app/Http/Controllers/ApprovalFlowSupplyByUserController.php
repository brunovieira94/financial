<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApprovalFlowSupplyByUserService;
use App\Http\Requests\PutAccountsPayableApprovalFlowRequest;

class ApprovalFlowSupplyByUserController extends Controller
{

    private $supplyApprovalFlowService;

    public function __construct(ApprovalFlowSupplyByUserService $supplyApprovalFlowService)
    {
        $this->supplyApprovalFlowService = $supplyApprovalFlowService;
    }

    public function accountsApproveUser(Request $request)
    {
        return $this->supplyApprovalFlowService->getAllAccountsForApproval($request->all());
    }

    public function approveAccount($id)
    {
        $this->supplyApprovalFlowService->approveAccount($id);
        return response('Conta aprovada');
    }

    public function approveManyAccounts(Request $request)
    {
        return $this->supplyApprovalFlowService->approveManyAccounts($request->all());
    }

    public function reproveAccount($id, PutAccountsPayableApprovalFlowRequest $request)
    {
        $this->supplyApprovalFlowService->reproveAccount($id, $request);
        return response('Conta reprovada');
    }

    public function cancelAccount($id, PutAccountsPayableApprovalFlowRequest $request)
    {
        $this->supplyApprovalFlowService->cancelAccount($id, $request);
        return response('Conta cancelada');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ApprovalFlowByUserService;
use App\Http\Requests\PutAccountsPayableApprovalFlowRequest;
use App\Exports\AccountsPayableApprovalFlowExport;
use App\Http\Requests\MultipleApproval;

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
        if(array_key_exists('exportFormat', $request->all()))
        {
            if($request->all()['exportFormat'] == 'csv')
            {
                return (new AccountsPayableApprovalFlowExport($request->all()))->download('contasAAprovar.csv', \Maatwebsite\Excel\Excel::CSV, ['Content-Type' => 'text/csv']);
            }
        }
        return (new AccountsPayableApprovalFlowExport($request->all()))->download('contasAAprovar.xlsx', \Maatwebsite\Excel\Excel::XLSX);
    }

    public function approveAccount($id)
    {
        return $this->accountsPayableApprovalFlowService->approveAccount($id);
    }

    public function approveManyAccounts(Request $request)
    {
        return $this->accountsPayableApprovalFlowService->approveManyAccounts($request->all());
    }

    public function reproveAccount($id, PutAccountsPayableApprovalFlowRequest $request)
    {
        return $this->accountsPayableApprovalFlowService->reproveAccount($id, $request);
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

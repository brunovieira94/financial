<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use App\Models\BillToPay;
use Illuminate\Http\Request;

class AccountsPayableApprovalFlowService
{
    private $accountsPayableApprovalFlow;
    private $approvalFlow;

    public function __construct(AccountsPayableApprovalFlow $accountsPayableApprovalFlow, ApprovalFlow $approvalFlow, BillToPay $billToPay)
    {
        $this->accountsPayableApprovalFlow = $accountsPayableApprovalFlow;
        $this->approvalFlow = $approvalFlow;
        $this->billToPay = $billToPay;
    }

    public function getAllAccountsForApproval($requestInfo)
    {
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->whereIn('order', $approvalFlowUserOrder->toArray())
        ->WhereIn('status', [0, 2])
        ->with(['bill_to_pay']),$requestInfo);
    }

    public function approveAccount($id)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = 0;

        if ($accountApproval->order == $maxOrder) {
            $accountApproval->status = 1;
        } else {
            $accountApproval->order += 1;
        }

        $accountApproval->reason = null;
        return $accountApproval->save();
    }

    public function reproveAccount($id, Request $request)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = 2;

        if ($accountApproval->order == 0) {
            return response('Não foi possível reprovar a conta.', 422);
        }
        if ($accountApproval->order > $maxOrder) {
            $accountApproval->order = 0;
        } else {
            $accountApproval->order -= 1;
        }
        $accountApproval->reason = $request->reason;
        $accountApproval->save();
        return true;
    }

    public function cancelAccount($id, Request $request)
    {

        $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($id);
        $accountApproval->status = 3;

        $accountApproval->reason = $request->reason;
        return $accountApproval->save();
    }
}

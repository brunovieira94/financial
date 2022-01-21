<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use App\Models\PaymentRequest;
use Illuminate\Http\Request;

class ApprovalFlowByUserService
{
    private $accountsPayableApprovalFlow;
    private $approvalFlow;

    public function __construct(AccountsPayableApprovalFlow $accountsPayableApprovalFlow, ApprovalFlow $approvalFlow)
    {
        $this->accountsPayableApprovalFlow = $accountsPayableApprovalFlow;
        $this->approvalFlow = $approvalFlow;
    }

    public function getAllAccountsForApproval($requestInfo)
    {
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->whereIn('order', $approvalFlowUserOrder->toArray())
        ->Where('status', 0)
        ->whereRelation('payment_request', 'deleted_at', '=', null)
        ->with(['payment_request, reason_to_reject']),$requestInfo);
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

        if ($accountApproval->order > $maxOrder) {
            $accountApproval->order = 0;
        } else if ($accountApproval->order == 0) {
            $accountApproval->reason = $request->reason;
        } else {
            $accountApproval->order -= 1;
        }
        $accountApproval->reason = $request->reason;

        $accountApproval->fill($request->all())->save();
        return true;
    }

    public function cancelAccount($id, Request $request)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($id);
        $accountApproval->status = 3;

        return $accountApproval->fill($request->all())->save();
    }
}

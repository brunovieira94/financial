<?php

namespace App\Services;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use Illuminate\Http\Request;

class AccountsPayableApprovalFlowService
{
    private $accountsPayableApprovalFlow;
    private $approvalFlow;

    public function __construct(AccountsPayableApprovalFlow $accountsPayableApprovalFlow, ApprovalFlow $approvalFlow)
    {
        $this->accountsPayableApprovalFlow = $accountsPayableApprovalFlow;
        $this->approvalFlow = $approvalFlow;
    }

    public function getAllAccountsForApproval()
    {
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);

        if(!$approvalFlowUser)
          return response([]);

        return $this->accountsPayableApprovalFlow->with('billToPay')->whereIn('order', $approvalFlowUserOrder->toArray())->WhereIn('status', [0,2])->get();
    }

    public function approveAccount($id)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');

        if ($accountApproval->order == $maxOrder){
            $accountApproval->status = 1;
        }else {
            $accountApproval->order += 1;
        }

        $accountApproval->reason = null;
        return $accountApproval->save();
    }

    public function reproveAccount($id, Request $request){

        $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');

        if ($accountApproval->order == 0){
            return response('Não foi possível reprovar a conta.', 422);
        }
        if ($accountApproval->order >= $maxOrder){
            $accountApproval->order -= $maxOrder;
        }else{
            $accountApproval->order -= $accountApproval->order;
        }

        $accountApproval->reason = $request->reason;
        $accountApproval->save();
        return true;
    }

    public function cancelAccount($id, Request $request){

        $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($id);
        $accountApproval->status = 3;

        $accountApproval->reason = $request->reason;
        return $accountApproval->save();
    }
}


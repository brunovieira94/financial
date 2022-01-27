<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use Illuminate\Http\Request;
use Config;

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
        ->with(['payment_request', 'reason_to_reject']),$requestInfo);
    }

    public function approveAccount($id)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->with('payment_request')->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = 0;

        if($accountApproval->order == $maxOrder) {
            if($accountApproval->payment_request->provider->accept_billet_payment){
                if($accountApproval->payment_request->bar_code == null){
                    response()->json([
                        'error' => 'O boleto não foi informado',

                    ], 422)->send();
                    die();
                }
            } else {
                if ($accountApproval->payment_request->invoice_number == null){
                    response()->json([
                        'error' => 'A nota fiscal não foi informada',
                    ], 422)->send();
                    die();
                }
                if($accountApproval->payment_request->bank_account_provider_id == null){
                    response()->json([
                        'error' => 'O banco do fornecedor não foi informado',
                    ], 422)->send();
                    die();
                }

            }
            $accountApproval->status = Config::get('constants.status.approved');
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
        $accountApproval->status = Config::get('constants.status.disapproved');

        if ($accountApproval->order > $maxOrder) {
            $accountApproval->order = Config::get('constants.status.open');
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
        $accountApproval->status = Config::get('constants.status.canceled');

        return $accountApproval->fill($request->all())->save();
    }
}

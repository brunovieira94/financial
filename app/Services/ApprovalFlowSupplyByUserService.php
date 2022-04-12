<?php

namespace App\Services;

use App\Models\SupplyApprovalFlow;
use App\Models\ApprovalFlowSupply;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Config;

class ApprovalFlowSupplyByUserService
{
    private $supplyApprovalFlow;
    private $approvalFlow;

    public function __construct(SupplyApprovalFlow $supplyApprovalFlow, ApprovalFlowSupply $approvalFlow, PurchaseOrder $purchaseOrder)
    {
        $this->supplyApprovalFlow = $supplyApprovalFlow;
        $this->approvalFlow = $approvalFlow;
        $this->purchaseOrder = $purchaseOrder;
    }

    public function getAllAccountsForApproval($requestInfo)
    {
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $supplyApprovalFlow = Utils::search($this->supplyApprovalFlow,$requestInfo);
        return Utils::pagination($supplyApprovalFlow
        ->whereIn('order', $approvalFlowUserOrder->toArray())
        ->where('status', 0)
        ->whereRelation('purchase_order', 'deleted_at', '=', null)
        ->with(['purchase_order', 'approval_flow']),$requestInfo);
    }

    public function approveAccount($id)
    {
        $accountApproval = $this->supplyApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = 0;

        if ($accountApproval->order == $maxOrder) {
            $accountApproval->status = Config::get('constants.status.approved');
        } else {
            $accountApproval->order += 1;
        }

        $accountApproval->reason = null;
        return $accountApproval->save();
    }

    public function reproveAccount($id, Request $request)
    {
        $accountApproval = $this->supplyApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = Config::get('constants.status.disapproved');

        if ($accountApproval->order > $maxOrder) {
            $accountApproval->order = 0;
        } else if ($accountApproval->order == 0) {
            $accountApproval->reason = $request->reason;
        } else {
            $accountApproval->order -= 1;
        }
        $accountApproval->reason = $request->reason;
        return $accountApproval->save();
    }

    public function cancelAccount($id, Request $request)
    {
        $accountApproval = $this->supplyApprovalFlow->findOrFail($id);
        $accountApproval->status = Config::get('constants.status.canceled');
        $accountApproval->reason = $request->reason;
        return $accountApproval->save();
    }

    public function getAllApprovedPurchaseOrder($requestInfo)
    {
        $accountApproval = Utils::search($this->supplyApprovalFlow,$requestInfo);
        return Utils::pagination($accountApproval
        ->with('purchase_order')
        ->whereRelation('purchase_order', 'deleted_at', '=', null)
        ->where('status', 1),$requestInfo);
    }
}

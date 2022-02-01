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
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id);

        $userCostCenter = auth()->user()->cost_center->map(function($e) {
            return $e->id;
        });

        if (!$approvalFlowUserOrder)
            return response([], 404);


        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'accounts_payable_approval_flows.id';
        return Utils::pagination($accountsPayableApprovalFlow
        ->join("approval_flow", "approval_flow.order", "=", "accounts_payable_approval_flows.order")
        ->select(['accounts_payable_approval_flows.*'])
        ->join("payment_requests", function($join) use ($userCostCenter) {
            $join->on("accounts_payable_approval_flows.payment_request_id", "=", "payment_requests.id")
            ->where(function($q) use ($userCostCenter) {
                $q->where(function($query) use ($userCostCenter) {
                    $query->where("approval_flow.filter_cost_center", true)
                    ->whereIn("payment_requests.cost_center_id", $userCostCenter);
                })
                ->orWhere(function($query) {
                    $query->where("approval_flow.filter_cost_center", false);
                });
            });

            // ->orWhere(function($query) {
            //     $query->where("approval_flow.filter_cost_center", false);
            // });
            //->whereIn("payment_requests.cost_center_id", $userCostCenter);
        })
        ->whereIn('accounts_payable_approval_flows.order', $approvalFlowUserOrder->get('order')->toArray())
        ->where('status', 0)
        ->whereRelation('payment_request', 'deleted_at', '=', null)
        ->with(['payment_request', 'reason_to_reject'])
        ->distinct(['accounts_payable_approval_flows.id'])
        , $requestInfo);

        // return $accountsPayableApprovalFlow
        // ->whereIn('order', $approvalFlowUserOrder->get('order')->toArray())
        // ->Where('status', 0)
        // ->whereRelation('payment_request', 'deleted_at', '=', null)
        // ->join('payment_requests', function($join) use ($userCostCenter) {
        //     $join->on("accounts_payable_approval_flows.payment_request_id", "=", "payment_requests.id")
        //     ->on("accounts_payable_approval_flows.filter_cost_center = true", function($q) use ($userCostCenter) {
        //         var_dump("asd");
        //         $q->whereIn("payment_requests.cost_center_id", $userCostCenter);
        //     });
        // })
        // ->with(['payment_request', 'reason_to_reject'])->get();
    }

    public function approveAccount($id)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->with('payment_request')->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = 0;

        if($accountApproval->order == $maxOrder) {
            if($accountApproval->payment_request->bar_code != null || $accountApproval->payment_request->invoice_number != null){
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
            } else {
                if($accountApproval->payment_request->bank_account_provider_id == null){
                    response()->json([
                        'error' => 'O banco do fornecedor não foi informado',
                    ], 422)->send();
                    die();
                }
            }
            $accountApproval->status = Config::get('constants.status.approved');
            $accountApproval->order += 1;
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
        } else if ($accountApproval->order != 0){
            $accountApproval->order -= 1;
        }
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

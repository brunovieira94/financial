<?php

namespace App\Services;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use App\Models\PaymentRequest;
use Carbon\Carbon;

class ReportService
{
    private $accountsPayableApprovalFlow;
    private $approvalFlow;

    public function __construct(AccountsPayableApprovalFlow $accountsPayableApprovalFlow, ApprovalFlow $approvalFlow, PaymentRequest $paymentRequest)
    {
        $this->accountsPayableApprovalFlow = $accountsPayableApprovalFlow;
        $this->approvalFlow = $approvalFlow;
        $this->paymentRequest = $paymentRequest;
    }

    public function getAllDuePaymentRequest($requestInfo)
    {
        $result = Utils::search($this->paymentRequest,$requestInfo);
        $result = $result->with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
        if(array_key_exists('from', $requestInfo)){
            $result = $result->where('pay_date', '>=', $requestInfo['from']);
        }
        if(array_key_exists('to', $requestInfo)){
            $result = $result->where('pay_date', '<=', $requestInfo['to']);
        }
        if(!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)){
            $result = $result->whereBetween('pay_date', [now(), now()->addMonths(1)]);
        }
        return Utils::pagination($result,$requestInfo);
    }

    public function getAllApprovedPaymentRequest($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request')
        ->whereRelation('payment_request', 'payment_type', '=', $requestInfo['payment_type'])
        ->where('status', 1),$requestInfo);
    }

    public function getAllGeneratedCNABPaymentRequest($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request')
        ->where('status', 6),$requestInfo);
    }

    public function getAllPaymentRequestPaid($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request')
        ->where('status', 4),$requestInfo);
    }

    public function getAllDisapprovedPaymentRequest($requestInfo){

        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id);

        $userCostCenter = auth()->user()->cost_center->map(function($e) {
            return $e->id;
        });

        if (!$approvalFlowUserOrder){
            return response([], 404);
        }

        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo,['order']);
        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'accounts_payable_approval_flows.id';
        return Utils::pagination($accountsPayableApprovalFlow
        ->join("approval_flow", "approval_flow.order", "=", "accounts_payable_approval_flows.order")
        ->select(['accounts_payable_approval_flows.*'])
        ->join("payment_requests", function($join) use ($userCostCenter) {
            $join->on("accounts_payable_approval_flows.payment_request_id", "=", "payment_requests.id")
            ->where(function($q) use ($userCostCenter) {
                if(!$userCostCenter->isEmpty()){
                $q->where(function($query) use ($userCostCenter) {
                    $query->where("approval_flow.filter_cost_center", true)
                    ->whereIn("payment_requests.cost_center_id", $userCostCenter);
                })
                ->orWhere(function($query) {
                    $query->where("approval_flow.filter_cost_center", false);
                });
            }
            });
        })
        ->whereIn('accounts_payable_approval_flows.order', $approvalFlowUserOrder->get('order')->toArray())
        ->where('status', 2)
        ->whereRelation('payment_request', 'deleted_at', '=', null)
        ->with(['payment_request', 'reason_to_reject'])
        ->distinct(['accounts_payable_approval_flows.id']),
        $requestInfo);
    }

    public function getAllPaymentRequestsDeleted($requestInfo){
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request_trashed')
        ->whereRelation('payment_request_trashed', 'deleted_at', '!=', null),
        $requestInfo);
    }

    public function getBillsToPay($requestInfo)
    {
        $query = $this->paymentRequest->query();
        $query = $query->with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
        if(array_key_exists('cpfcnpj', $requestInfo)){
            $query->whereHas('provider', function ($query) use ($requestInfo){
                $query->where('cpf', $requestInfo['cpfcnpj'])->orWhere('cnpj', $requestInfo['cpfcnpj']);
            });
        }
        if(array_key_exists('provider', $requestInfo)){
            $query->whereHas('provider', function ($query) use ($requestInfo){
                $query->where('id', $requestInfo['provider']);
            });
        }
        if(array_key_exists('chart_of_accounts', $requestInfo)){
            $query->whereHas('chart_of_accounts', function ($query) use ($requestInfo){
                $query->where('id', $requestInfo['chart_of_accounts']);
            });
        }
        if(array_key_exists('cost_center', $requestInfo)){
            $query->whereHas('cost_center', function ($query) use ($requestInfo){
                $query->where('id', $requestInfo['cost_center']);
            });
        }
        if(array_key_exists('payment_request', $requestInfo)){
            $query->where('id', $requestInfo['payment_request']);
        }
        if(array_key_exists('user', $requestInfo)){
            $query->whereHas('user', function ($query) use ($requestInfo){
                $query->where('id', $requestInfo['user']);
            });
        }
        if(array_key_exists('status', $requestInfo)){
            $query->whereHas('approval', function ($query) use ($requestInfo){
                $query->where('status', $requestInfo['status']);
            });
        }
        if(array_key_exists('approval_order', $requestInfo)){
            $query->whereHas('approval', function ($query) use ($requestInfo){
                $query->where('order', $requestInfo['approval_order']);
            });
        }
        if(array_key_exists('created_at', $requestInfo)){
            if(array_key_exists('from', $requestInfo['created_at'])){
                $query->where('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if(array_key_exists('to', $requestInfo['created_at'])){
                $query->where('created_at', '<=', $requestInfo['created_at']['to']);
            }
            if(!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])){
                $query->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if(array_key_exists('pay_date', $requestInfo)){
            if(array_key_exists('from', $requestInfo['pay_date'])){
                $query->where('pay_date', '>=', $requestInfo['pay_date']['from']);
            }
            if(array_key_exists('to', $requestInfo['pay_date'])){
                $query->where('pay_date', '<=', $requestInfo['pay_date']['to']);
            }
            if(!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])){
                $query->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if(array_key_exists('extension_date', $requestInfo)){
            if(array_key_exists('from', $requestInfo['extension_date'])){
                $query->whereHas('installments', function ($query) use ($requestInfo){
                    $query->where('extension_date', '>=', $requestInfo['extension_date']['from']);
                });
            }
            if(array_key_exists('to', $requestInfo['extension_date'])){
                $query->whereHas('installments', function ($query) use ($requestInfo){
                    $query->where('extension_date', '<=', $requestInfo['extension_date']['to']);
                });
            }
            if(!array_key_exists('to', $requestInfo['extension_date']) && !array_key_exists('from', $requestInfo['extension_date'])){
                $query->whereHas('installments', function ($query) use ($requestInfo){
                    $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
                });
            }
        }
        if(array_key_exists('days_late', $requestInfo)){
            $query->whereHas('installments', function ($query) use ($requestInfo){
                $query->where('status', '!=', 'BD')->orWhereNull('status')->whereDate("due_date", "<=", Carbon::now()->subDays($requestInfo['days_late']));
            });
        }


        //whereDate("due_date", "<=", Carbon::now().subDays($days_late))
        return Utils::pagination($query,$requestInfo);
    }

    public function getAllPaymentRequestFinished($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request')
        ->where('status', 7),$requestInfo);
    }
}


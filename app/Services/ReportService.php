<?php

namespace App\Services;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use App\Models\PaymentRequest;

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
        $result = $result->with(['approval', 'installments', 'provider', 'bank_account_provider', 'bank_account_company', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
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
        //$approvalFlowOrder = $requestInfo['approvalFlowOrder'] ?? $this->approvalFlow->max('order');
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request')
        //->where('order', $approvalFlowOrder)
        ->where('status', 1),$requestInfo);
    }

    public function getAllDisapprovedPaymentRequest($requestInfo){
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request')
        ->where('status', 2),
        $requestInfo);
    }

    public function getAllPaymentRequestsDeleted($requestInfo){
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request_trashed')
        ->whereRelation('payment_request_trashed', 'deleted_at', '!=', null),
        $requestInfo);
    }
}


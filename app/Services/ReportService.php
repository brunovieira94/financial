<?php

namespace App\Services;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use App\Models\BillToPay;

class ReportService
{
    private $accountsPayableApprovalFlow;
    private $approvalFlow;

    public function __construct(AccountsPayableApprovalFlow $accountsPayableApprovalFlow, ApprovalFlow $approvalFlow, BillToPay $billToPay)
    {
        $this->accountsPayableApprovalFlow = $accountsPayableApprovalFlow;
        $this->approvalFlow = $approvalFlow;
        $this->billToPay = $billToPay;
    }

    public function getAllDueBills($requestInfo)
    {
        $result = Utils::search($this->billToPay,$requestInfo);
        $result = $result->with(['installments', 'provider', 'bank_account_provider', 'bank_account_company', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
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

    public function getAllApprovedBills($requestInfo)
    {
        $approvalFlowOrder = $requestInfo['approvalFlowOrder'] ?? $this->approvalFlow->max('order');
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow->with('bill_to_pay')->where('order', $approvalFlowOrder)->where('status', 1),$requestInfo);
    }
}


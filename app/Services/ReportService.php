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
        $result = $result->with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'bank_account_company', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
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
        $approvalFlowOrder = $requestInfo['approvalFlowOrder'] ?? $this->approvalFlow->max('order');
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request')
        ->where('order', $approvalFlowOrder)
        ->where('status', 1),$requestInfo);
    }

    public function getAllDisapprovedPaymentRequest($requestInfo){
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with(['payment_request', 'reason_to_reject'])
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

    public function getAllApprovedPaymentRequestCNAB($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow,$requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
        ->with('payment_request')
        ->whereRelation('payment_request', 'bar_code', '!=', null)
        ->where('status', 1),$requestInfo);
    }

    public function getBillsToPay($requestInfo)
    {
        $query = $this->paymentRequest->query();
        $query = $query->with(['tax', 'approval', 'installments', 'provider', 'bank_account_provider', 'bank_account_company', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
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
        if(array_key_exists('approvalOrder', $requestInfo)){
            $query->whereHas('approval', function ($query) use ($requestInfo){
                $query->where('order', $requestInfo['approvalOrder']);
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
}


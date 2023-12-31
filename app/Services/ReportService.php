<?php

namespace App\Services;

use App\Http\Resources\reports\RouteApprovalUserResource;
use App\Http\Resources\reports\RouteApprovedPaymentRequest;
use App\Http\Resources\reports\RouteBillToPayResource;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\AccountsPayableApprovalFlowLog;
use App\Models\ApprovalFlow;
use App\Models\CnabGenerated;
use App\Models\FormPayment;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasInstallments;
use App\Models\PaymentRequestHasInstallmentsClean;
use App\Models\SupplyApprovalFlow;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private $accountsPayableApprovalFlow;
    private $supplyApprovalFlow;
    private $approvalFlow;
    private $filterCanceled = false;
    private $cnabGenerated;
    private $installment;
    private $paymentRequest;
    private $paymentRequestClean;
    private $accountsPayableApprovalFlowClean;
    private $installmentClean;
    private $accountsPayableApprovalFlowLog;

    private $paymentRequestCleanWith = ['provider', 'cost_center', 'approval.approval_flow', 'installments', 'currency', 'cnab_payment_request.cnab_generated'];
    private $installmentCleanWith = ['payment_request.provider.provider_category', 'payment_request.cost_center', 'payment_request.approval.approval_flow', 'payment_request.currency', 'cnab_generated_installment.generated_cnab', 'bank_account_provider', 'group_payment.form_payment'];
    private $accountsPayableApprovalFlowCleanWith = ['payment_request.provider', 'payment_request.cost_center', 'payment_request.approval.approval_flow', 'payment_request.currency', 'payment_request.cnab_payment_request.cnab_generated', 'payment_request.installments.bank_account_provider'];
    private $logPaymentRequestWith = ['user', 'payment_request.provider'];

    public function __construct(AccountsPayableApprovalFlowLog $accountsPayableApprovalFlowLog, PaymentRequestHasInstallmentsClean $installmentClean, AccountsPayableApprovalFlowClean $accountsPayableApprovalFlowClean, PaymentRequestClean $paymentRequestClean, PaymentRequestHasInstallments $installment, AccountsPayableApprovalFlow $accountsPayableApprovalFlow, ApprovalFlow $approvalFlow, PaymentRequest $paymentRequest, SupplyApprovalFlow $supplyApprovalFlow, CnabGenerated $cnabGenerated)
    {
        $this->accountsPayableApprovalFlow = $accountsPayableApprovalFlow;
        $this->approvalFlow = $approvalFlow;
        $this->paymentRequest = $paymentRequest;
        $this->supplyApprovalFlow = $supplyApprovalFlow;
        $this->cnabGenerated = $cnabGenerated;
        $this->installment = $installment;
        $this->accountsPayableApprovalFlowClean = $accountsPayableApprovalFlowClean;
        $this->paymentRequestClean = $paymentRequestClean;
        $this->installmentClean = $installmentClean;
        $this->accountsPayableApprovalFlowLog = $accountsPayableApprovalFlowLog;
    }

    public function getAllDuePaymentRequest($requestInfo)
    {
        $result = Utils::search($this->paymentRequestClean, $requestInfo);
        $result = $result->with($this->paymentRequestCleanWith);
        $result = Utils::baseFilterReportsPaymentRequest($result, $requestInfo);

        $result = $result->whereHas('installments', function ($query) use ($requestInfo) {
            if (array_key_exists('from', $requestInfo)) {
                $query = $query->where('extension_date', '>=', $requestInfo['from']);
            }
            if (array_key_exists('to', $requestInfo)) {
                $query = $query->where('extension_date', '<=', $requestInfo['to']);
            }
            if (!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)) {
                $query = $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
            }
        });
        return Utils::pagination($result, $requestInfo);
    }

    public function getAllDueInstallment($requestInfo)
    {
        $result = Utils::search($this->installmentClean, $requestInfo);
        $result = $result->with($this->installmentCleanWith)->has('payment_request');

        $result = $result->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        $result = Utils::baseFilterReportsInstallment($result, $requestInfo);

        if (array_key_exists('from', $requestInfo)) {
            $result = $result->where('extension_date', '>=', $requestInfo['from']);
        }
        if (array_key_exists('to', $requestInfo)) {
            $result = $result->where('extension_date', '<=', $requestInfo['to']);
        }
        if (!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)) {
            $result = $result->whereBetween('extension_date', [now(), now()->addMonths(1)]);
        }
        return Utils::pagination($result, $requestInfo);
    }

    public function getAllApprovedPaymentRequest($requestInfo)
    {
        $paymentRequest = $this->paymentRequestClean->query();
        $paymentRequest = $paymentRequest->with($this->paymentRequestCleanWith);
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);
        $paymentRequest = $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
            $query = $query->where('status', 1);
        });

        if (!array_key_exists('company', $requestInfo)) {
            return response()->json([
                'current_page' => 1,
                'data' => [],
                'from' => null,
                'last_page' => 1,
                'per_page' => 20,
                'to' => null,
                'total' => 0
            ], 200);
        }

        return RouteApprovedPaymentRequest::collection(Utils::pagination($paymentRequest, $requestInfo));
    }

    public function getAllApprovedInstallment($requestInfo)
    {
        $installment = Utils::search($this->installmentClean, $requestInfo);
        $installment = $installment->with($this->installmentCleanWith);

        $installment = $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('status', 1);
            });
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        $installment = Utils::baseFilterReportsInstallment($installment, $requestInfo);
        if (!array_key_exists('company', $requestInfo)) {
            return response()->json([
                'current_page' => 1,
                'data' => [],
                'from' => null,
                'last_page' => 1,
                'per_page' => 20,
                'to' => null,
                'total' => 0
            ], 200);
        }
        return Utils::pagination($installment, $requestInfo);
    }

    public function getAllGeneratedCNABPaymentRequest($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow, $requestInfo);
        return Utils::pagination($accountsPayableApprovalFlow
            ->where('status', 6), $requestInfo);
    }

    public function getAllPaymentRequestPaid($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlowClean, $requestInfo);
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->with($this->accountsPayableApprovalFlowCleanWith);
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });
        return Utils::pagination($accountsPayableApprovalFlow
            ->where('status', 4), $requestInfo);
    }

    public function getAllDisapprovedPaymentRequest($requestInfo)
    {
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlowClean, $requestInfo, ['order']);
        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'accounts_payable_approval_flows.id';
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });

        return Utils::pagination($accountsPayableApprovalFlow
            ->whereIn('order', $approvalFlowUserOrder->toArray())
            ->where('status', 2)
            ->whereRelation('payment_request', 'deleted_at', '=', null)
            ->with($this->accountsPayableApprovalFlowCleanWith), $requestInfo);
    }

    public function getAllPaymentRequestsDeleted($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlowClean, $requestInfo);
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->whereHas('payment_request_trashed', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });
        return Utils::pagination(
            $accountsPayableApprovalFlow
                ->with(['payment_request_trashed.provider', 'payment_request_trashed.cost_center', 'payment_request_trashed.approval.approval_flow', 'payment_request_trashed.currency', 'payment_request_trashed.cnab_payment_request.cnab_generated'])
                ->whereRelation('payment_request_trashed', 'deleted_at', '!=', null),
            $requestInfo
        );
    }

    public function getBillsToPay($requestInfo)
    {
        $paymentRequest = $this->paymentRequestClean->query();
        $paymentRequest = $paymentRequest->with($this->paymentRequestCleanWith);
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);

        //whereDate("due_date", "<=", Carbon::now().subDays($days_late))
        return RouteBillToPayResource::collection(Utils::pagination($paymentRequest, $requestInfo));
    }

    public function getInstallmentsPayable($requestInfo)
    {
        $query = $this->installmentClean->query();
        $query = $query->with($this->installmentCleanWith);
        $query->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo, true);
        });
        $query = Utils::baseFilterReportsInstallment($query, $requestInfo);
        return Utils::pagination($query, $requestInfo);
    }

    public function getAllPaymentRequestFinished($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlowClean, $requestInfo);
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });
        return Utils::pagination($accountsPayableApprovalFlow
            ->with($this->accountsPayableApprovalFlowCleanWith)
            ->where('status', 7), $requestInfo);
    }

    public function getAllApprovedPurchaseOrder($requestInfo)
    {
        $accountApproval = Utils::search($this->supplyApprovalFlow, $requestInfo);

        if (auth()->user()->role->filter_cost_center_supply) {
            $purchaseOrderIds = [];
            foreach (auth()->user()->cost_center as $userCostCenter) {

                $purchaseOrderCostCenters = $this->supplyApprovalFlow->whereHas('purchase_order', function ($query) use ($userCostCenter) {
                    $query->whereHas('cost_centers', function ($cost_centers) use ($userCostCenter) {
                        $cost_centers->where('cost_center_id', $userCostCenter->id);
                    });
                })->get(['id_purchase_order']);

                foreach ($purchaseOrderCostCenters as $purchaseOrderCostCenter) {
                    $purchaseOrderIds[] = $purchaseOrderCostCenter->id_purchase_order;
                }
            }

            $accountApproval->whereIn('id_purchase_order', $purchaseOrderIds);
        }

        $accountApproval->whereHas('purchase_order', function ($query) use ($requestInfo) {
            if (array_key_exists('provider', $requestInfo)) {
                $query->where('provider_id', $requestInfo['provider']);
            }
            if (array_key_exists('cost_center', $requestInfo)) {
                $query->whereHas('cost_centers', function ($cost_centers) use ($requestInfo) {
                    $cost_centers->where('cost_center_id', $requestInfo['cost_center']);
                });
            }
            if (array_key_exists('service', $requestInfo)) {
                $query->whereHas('services', function ($services) use ($requestInfo) {
                    $services->where('service_id', $requestInfo['service']);
                });
            }
            if (array_key_exists('product', $requestInfo)) {
                $query->whereHas('products', function ($products) use ($requestInfo) {
                    $products->where('product_id', $requestInfo['product']);
                });
            }

            if (array_key_exists('billing_date', $requestInfo)) {
                if (array_key_exists('from', $requestInfo['billing_date'])) {
                    $query->where('billing_date', '>=', $requestInfo['billing_date']['from']);
                }
                if (array_key_exists('to', $requestInfo['billing_date'])) {
                    $query->where('billing_date', '<=', $requestInfo['billing_date']['to']);
                }
            }

            if (array_key_exists('approval_date', $requestInfo)) {
                $query->whereHas('approval', function ($approval) use ($requestInfo) {
                    if (array_key_exists('from', $requestInfo['approval_date'])) {
                        $approval->whereDate('updated_at', '>=', $requestInfo['approval_date']['from']);
                    }
                    if (array_key_exists('to', $requestInfo['approval_date'])) {
                        $approval->whereDate('updated_at', '<=', $requestInfo['approval_date']['to']);
                    }
                });
            }
        });

        return Utils::pagination($accountApproval
            ->with('purchase_order')
            ->with('purchase_order.installments')
            ->whereRelation('purchase_order', 'deleted_at', '=', null)
            ->where('status', 1), $requestInfo);
    }

    public function getAllApprovedPurchaseOrderForIntegration($requestInfo)
    {
        $accountApproval = $this->supplyApprovalFlow;
        if (auth()->user()->role->filter_cost_center_supply) {
            $purchaseOrderIds = [];
            foreach (auth()->user()->cost_center as $userCostCenter) {

                $purchaseOrderCostCenters = $this->supplyApprovalFlow->whereHas('purchase_order', function ($query) use ($userCostCenter) {
                    $query->whereHas('cost_centers', function ($cost_centers) use ($userCostCenter) {
                        $cost_centers->where('cost_center_id', $userCostCenter->id);
                    });
                })->get(['id_purchase_order']);

                foreach ($purchaseOrderCostCenters as $purchaseOrderCostCenter) {
                    $purchaseOrderIds[] = $purchaseOrderCostCenter->id_purchase_order;
                }
            }

            $accountApproval = $accountApproval->whereIn('id_purchase_order', $purchaseOrderIds);
        }

        $accountApproval = $accountApproval->whereHas('purchase_order', function ($query) use ($requestInfo) {
            if (array_key_exists('provider', $requestInfo)) {
                $query->where('provider_id', $requestInfo['provider']);
            }
            if (array_key_exists('cost_center', $requestInfo)) {
                $query->whereHas('cost_centers', function ($cost_centers) use ($requestInfo) {
                    $cost_centers->where('cost_center_id', $requestInfo['cost_center']);
                });
            }
            if (array_key_exists('service', $requestInfo)) {
                $query->whereHas('services', function ($services) use ($requestInfo) {
                    $services->where('service_id', $requestInfo['service']);
                });
            }
            if (array_key_exists('product', $requestInfo)) {
                $query->whereHas('products', function ($products) use ($requestInfo) {
                    $products->where('product_id', $requestInfo['product']);
                });
            }

            if (array_key_exists('billing_date', $requestInfo)) {
                if (array_key_exists('from', $requestInfo['billing_date'])) {
                    $query->where('billing_date', '>=', $requestInfo['billing_date']['from']);
                }
                if (array_key_exists('to', $requestInfo['billing_date'])) {
                    $query->where('billing_date', '<=', $requestInfo['billing_date']['to']);
                }
            }
        });

        return Utils::pagination($accountApproval
            ->with('purchase_order')
            ->has('purchase_order.installments_integration')
            ->with('purchase_order.installments_integration')
            ->whereRelation('purchase_order', 'deleted_at', '=', null)
            ->where('status', 1), $requestInfo);
    }

    public function getAllCnabGenerate($requestInfo)
    {
        $cnabGenerated = Utils::search($this->cnabGenerated, $requestInfo);

        return Utils::pagination(
            $cnabGenerated
                ->with(['user', 'company', 'bank_account_company.bank']),
            $requestInfo
        );
    }

    public function getCnabGenerate($requestInfo, $id)
    {
        return $this->cnabGenerated->with(['user', 'company', 'payment_requests.installments_cnab.installment.bank_account_provider', 'bank_account_company.bank', 'payment_requests.payment_request'])->findOrFail($id);
    }

    public function getUserApprovalsReport($requestInfo)
    {
        $logPaymentRequest = Utils::search($this->accountsPayableApprovalFlowLog, $requestInfo);
        $logPaymentRequest = $logPaymentRequest->with($this->logPaymentRequestWith);
        if (!array_key_exists('user_approval_id', $requestInfo)) {
            return response()->json([
                'current_page' => 1,
                'data' => [],
                'from' => null,
                'last_page' => 1,
                'per_page' => 20,
                'to' => null,
                'total' => 0
            ], 200);
        }
        $logPaymentRequest = $logPaymentRequest->whereIn('user_id',  (array)$requestInfo['user_approval_id']);
        $logPaymentRequest = $logPaymentRequest->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query = Utils::baseFilterReportsPaymentRequest($query, $requestInfo);
        });

        if (array_key_exists('date_approval', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['date_approval'])) {
                $logPaymentRequest = $logPaymentRequest->where('created_at', '>=', $requestInfo['date_approval']['from']);
            }
            if (array_key_exists('to', $requestInfo['date_approval'])) {
                $logPaymentRequest = $logPaymentRequest->where('created_at', '<=', $requestInfo['date_approval']['to']);
            }
            if (!array_key_exists('to', $requestInfo['date_approval']) && !array_key_exists('from', $requestInfo['date_approval'])) {
                $logPaymentRequest = $logPaymentRequest->whereBetween('created_at', [now(), now()->addMonths(1)]);
            }
        }

        if (array_key_exists('status_approval', $requestInfo)) {
            $logPaymentRequest = $logPaymentRequest->where('type',  $requestInfo['status_approval']);
        }

        return RouteApprovalUserResource::collection(Utils::pagination($logPaymentRequest, $requestInfo));
    }
}

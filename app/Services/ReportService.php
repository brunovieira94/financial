<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
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

    private $paymentRequestCleanWith = ['provider', 'cost_center', 'approval.approval_flow', 'installments', 'currency', 'cnab_payment_request.cnab_generated'];
    private $installmentCleanWith = ['payment_request.provider', 'payment_request.cost_center', 'payment_request.approval.approval_flow', 'payment_request.currency', 'cnab_payment_request.cnab_generated', 'group_payment.form_payment', 'bank_account_provider'];
    private $accountsPayableApprovalFlowCleanWith = ['payment_request.provider', 'payment_request.cost_center', 'payment_request.approval.approval_flow', 'payment_request.currency', 'payment_request.cnab_payment_request.cnab_generated'];

    public function __construct(PaymentRequestHasInstallmentsClean $installmentClean, AccountsPayableApprovalFlowClean $accountsPayableApprovalFlowClean, PaymentRequestClean $paymentRequestClean, PaymentRequestHasInstallments $installment, AccountsPayableApprovalFlow $accountsPayableApprovalFlow, ApprovalFlow $approvalFlow, PaymentRequest $paymentRequest, SupplyApprovalFlow $supplyApprovalFlow, CnabGenerated $cnabGenerated)
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
    }

    public function getAllDuePaymentRequest($requestInfo)
    {
        $result = Utils::search($this->paymentRequestClean, $requestInfo);
        $result = $result->with($this->paymentRequestCleanWith);
        if (array_key_exists('from', $requestInfo)) {
            $result = $result->where('pay_date', '>=', $requestInfo['from']);
        }
        if (array_key_exists('to', $requestInfo)) {
            $result = $result->where('pay_date', '<=', $requestInfo['to']);
        }
        if (!array_key_exists('to', $requestInfo) && !array_key_exists('from', $requestInfo)) {
            $result = $result->whereBetween('pay_date', [now(), now()->addMonths(1)]);
        }
        return Utils::pagination($result, $requestInfo);
    }

    public function getAllDueInstallment($requestInfo)
    {
        $result = Utils::search($this->installmentClean, $requestInfo);
        $result = $result->with($this->installmentCleanWith)->has('payment_request');

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
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlowClean, $requestInfo);
        $accountsPayableApprovalFlow = $accountsPayableApprovalFlow->with($this->accountsPayableApprovalFlowCleanWith);

        if ((!array_key_exists('form_payment_id', $requestInfo) || $requestInfo['form_payment_id'] == 0) && array_key_exists('company_id', $requestInfo)) {
            return Utils::pagination($accountsPayableApprovalFlow
                ->whereRelation('payment_request', 'deleted_at', '=', null)
                ->whereRelation('payment_request', 'company_id', '=', $requestInfo['company_id'])
                ->where('status', 1), $requestInfo);
        } elseif (!array_key_exists('form_payment_id', $requestInfo) || $requestInfo['form_payment_id'] == 0) {
            return Utils::pagination($accountsPayableApprovalFlow
                ->whereRelation('payment_request', 'deleted_at', '=', null)
                ->where('status', 1), $requestInfo);
        }

        $formPayment = FormPayment::findOrFail($requestInfo['form_payment_id']);

        if ($formPayment->group_form_payment_id == 1) {
            if ($formPayment->same_ownership) {
                if (!array_key_exists('company_id', $requestInfo)) {
                    return Utils::pagination($accountsPayableApprovalFlow
                        ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id)
                        ->whereRelation('payment_request', 'deleted_at', '=', null)
                        ->whereRelation('payment_request', 'bar_code', 'like', "{$formPayment->bank_code}%")
                        ->where('status', 1), $requestInfo);
                } else {
                    return Utils::pagination($accountsPayableApprovalFlow
                        ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id)
                        ->whereRelation('payment_request', 'deleted_at', '=', null)
                        ->whereRelation('payment_request', 'bar_code', 'like', "{$formPayment->bank_code}%")
                        ->whereRelation('payment_request', 'company_id', '=', $requestInfo['company_id'])
                        ->where('status', 1), $requestInfo);
                }
            } else {
                if (!array_key_exists('company_id', $requestInfo)) {
                    return Utils::pagination($accountsPayableApprovalFlow
                        ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id)
                        ->whereRelation('payment_request', 'deleted_at', '=', null)
                        ->whereRelation('payment_request', 'bar_code', 'not like', "{$formPayment->bank_code}%")
                        ->where('status', 1), $requestInfo);
                } else {
                    return Utils::pagination($accountsPayableApprovalFlow
                        ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id)
                        ->whereRelation('payment_request', 'deleted_at', '=', null)
                        ->whereRelation('payment_request', 'bar_code', 'not like', "{$formPayment->bank_code}%")
                        ->whereRelation('payment_request', 'company_id', '=', $requestInfo['company_id'])
                        ->where('status', 1), $requestInfo);
                }
            }
        } else {
            if (!array_key_exists('company_id', $requestInfo)) {
                return Utils::pagination($accountsPayableApprovalFlow
                    ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id) // arrumar
                    ->whereRelation('payment_request', 'deleted_at', '=', null)
                    ->where('status', 1), $requestInfo);
            } else {
                return Utils::pagination($accountsPayableApprovalFlow
                    ->whereRelation('payment_request', 'group_form_payment_id', '=', $formPayment->group_form_payment_id) // arrumar
                    ->whereRelation('payment_request', 'deleted_at', '=', null)
                    ->whereRelation('payment_request', 'company_id', '=', $requestInfo['company_id'])
                    ->where('status', 1), $requestInfo);
            }
        }
    }

    public function getAllApprovedInstallment($requestInfo)
    {
        $installment = Utils::search($this->installmentClean, $requestInfo);
        $installment = $installment->with($this->installmentCleanWith);

        $installment = $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
            $query->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('status', 1);
            });
        });

        if (!array_key_exists('company_id', $requestInfo)) {
            return Utils::pagination($installment, $requestInfo);
        } else {
            $installment = $installment->whereHas('payment_request', function ($query) use ($requestInfo) {
                $query->where('company_id', $requestInfo['company_id']);
            });
            return Utils::pagination($installment, $requestInfo);
        }
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

        return Utils::pagination($accountsPayableApprovalFlow
            ->whereIn('order', $approvalFlowUserOrder->toArray())
            ->where('status', 2)
            ->whereRelation('payment_request', 'deleted_at', '=', null)
            ->with($this->accountsPayableApprovalFlowCleanWith), $requestInfo);
    }

    public function getAllPaymentRequestsDeleted($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlowClean, $requestInfo);
        return Utils::pagination(
            $accountsPayableApprovalFlow
                ->with(['payment_request_trashed.provider', 'payment_request_trashed.cost_center', 'payment_request_trashed.approval.approval_flow', 'payment_request_trashed.currency', 'payment_request_trashed.cnab_payment_request.cnab_generated'])
                ->whereRelation('payment_request_trashed', 'deleted_at', '!=', null),
            $requestInfo
        );
    }

    public function getBillsToPay($requestInfo)
    {
        $query = $this->paymentRequestClean->query();
        $query = $query->with($this->paymentRequestCleanWith);


        if (array_key_exists('amount', $requestInfo)) {
            $query->where('amount', $requestInfo['amount']);
        }
        if (array_key_exists('net_value', $requestInfo)) {
            $query->where('net_value', $requestInfo['net_value']);
        }
        if (array_key_exists('cpfcnpj', $requestInfo)) {
            $query->whereHas('provider', function ($query) use ($requestInfo) {
                $query->where('cpf', $requestInfo['cpfcnpj'])->orWhere('cnpj', $requestInfo['cpfcnpj']);
            });
        }
        if (array_key_exists('provider', $requestInfo)) {
            $query->whereHas('provider', function ($query) use ($requestInfo) {
                $query->where('id', $requestInfo['provider']);
            });
        }
        if (array_key_exists('company', $requestInfo)) {
            $query->whereHas('company', function ($query) use ($requestInfo) {
                $query->where('id', $requestInfo['company']);
            });
        }
        if (array_key_exists('chart_of_accounts', $requestInfo)) {
            $query->whereHas('chart_of_accounts', function ($query) use ($requestInfo) {
                $query->where('id', $requestInfo['chart_of_accounts']);
            });
        }
        if (array_key_exists('cost_center', $requestInfo)) {
            $query->whereHas('cost_center', function ($query) use ($requestInfo) {
                $query->where('id', $requestInfo['cost_center']);
            });
        }
        if (array_key_exists('payment_request', $requestInfo)) {
            $query->where('id', $requestInfo['payment_request']);
        }
        if (array_key_exists('user', $requestInfo)) {
            $query->whereHas('user', function ($query) use ($requestInfo) {
                $query->where('id', $requestInfo['user']);
            });
        }
        if (array_key_exists('status', $requestInfo)) {
            $query->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('status', $requestInfo['status']);
                if ($requestInfo['status'] == 3) {
                    $this->filterCanceled = true;
                }
            });
        }
        if (array_key_exists('approval_order', $requestInfo)) {
            $query->whereHas('approval', function ($query) use ($requestInfo) {
                $query->where('order', $requestInfo['approval_order']);
            });
        }
        if (array_key_exists('created_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['created_at'])) {
                $query->where('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['created_at'])) {
                $query->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
            }
            if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                $query->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['pay_date'])) {
                $query->where('pay_date', '>=', $requestInfo['pay_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['pay_date'])) {
                $query->where('pay_date', '<=', $requestInfo['pay_date']['to']);
            }
            if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                $query->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if (array_key_exists('extension_date', $requestInfo)) {

            $installments = DB::select("SELECT id as id_payment_request, (select
            id as id_payment_requests_installments
            FROM api.payment_requests_installments
            WHERE payment_request_id = id_payment_request
            AND status <> 4
            AND status <> 7
            ORDER BY extension_date asc
            LIMIT 1) AS id_installment
            FROM api.payment_requests");

            $installmentIDs = [];

            foreach ($installments as $installment) {
                if ($installment->id_installment != null) {
                    array_push($installmentIDs, $installment->id_installment);
                }
            }
            $requestInfo['installmentsIds'] = $installmentIDs;

            $query->whereHas('installments', function ($query) use ($requestInfo) {
                $query->whereIn('id', $requestInfo['installmentsIds']);
                if (array_key_exists('from', $requestInfo['extension_date'])) {
                    $query->where('extension_date', '>=', $requestInfo['extension_date']['from']);
                }
                if (array_key_exists('to', $requestInfo['extension_date'])) {
                    $query->where('extension_date', '<=', $requestInfo['extension_date']['to']);
                }
                if (!array_key_exists('to', $requestInfo['extension_date']) && !array_key_exists('from', $requestInfo['extension_date'])) {
                    $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
                }
            });
        }
        if (array_key_exists('cnab_date', $requestInfo)) {
            $query->whereHas('cnab_payment_request', function ($cnabPaymentRequest) use ($requestInfo) {
                $cnabPaymentRequest->whereHas('cnab_generated', function ($cnabGenerated) use ($requestInfo) {
                    if (array_key_exists('from', $requestInfo['cnab_date'])) {
                        $cnabGenerated->where('file_date', '>=', $requestInfo['cnab_date']['from']);
                    }
                    if (array_key_exists('to', $requestInfo['cnab_date'])) {
                        $cnabGenerated->where('file_date', '<=', $requestInfo['cnab_date']['to']);
                    }
                    if (!array_key_exists('to', $requestInfo['cnab_date']) && !array_key_exists('from', $requestInfo['cnab_date'])) {
                        $cnabGenerated->whereBetween('file_date', [now(), now()->addMonths(1)]);
                    }
                });
            });
        }

        if ($this->filterCanceled) {
            $query->withTrashed();
            $query->where('deleted_at', '!=', NULL);
        }

        //whereDate("due_date", "<=", Carbon::now().subDays($days_late))
        return Utils::pagination($query, $requestInfo);
    }

    public function getInstallmentsPayable($requestInfo)
    {
        $query = $this->installmentClean->query();
        $query = $query->with($this->installmentCleanWith);

        $query->whereHas('payment_request', function ($query) use ($requestInfo) {
            if (array_key_exists('net_value', $requestInfo)) {
                $query->where('net_value', $requestInfo['net_value']);
            }
            if (array_key_exists('amount', $requestInfo)) {
                $query->where('amount', $requestInfo['amount']);
            }
            if (array_key_exists('cpfcnpj', $requestInfo)) {
                $query->whereHas('provider', function ($query) use ($requestInfo) {
                    $query->where('cpf', $requestInfo['cpfcnpj'])->orWhere('cnpj', $requestInfo['cpfcnpj']);
                });
            }
            if (array_key_exists('provider', $requestInfo)) {
                $query->whereHas('provider', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['provider']);
                });
            }
            if (array_key_exists('chart_of_accounts', $requestInfo)) {
                $query->whereHas('chart_of_accounts', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['chart_of_accounts']);
                });
            }
            if (array_key_exists('cost_center', $requestInfo)) {
                $query->whereHas('cost_center', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['cost_center']);
                });
            }
            if (array_key_exists('payment_request', $requestInfo)) {
                $query->where('id', $requestInfo['payment_request']);
            }
            if (array_key_exists('user', $requestInfo)) {
                $query->whereHas('user', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['user']);
                });
            }
            if (array_key_exists('status', $requestInfo)) {
                $query->whereHas('approval', function ($query) use ($requestInfo) {
                    $query->where('status', $requestInfo['status']);
                    if ($requestInfo['status'] == 3) {
                        $this->filterCanceled = true;
                    }
                });
            }
            if (array_key_exists('approval_order', $requestInfo)) {
                $query->whereHas('approval', function ($query) use ($requestInfo) {
                    $query->where('order', $requestInfo['approval_order']);
                });
            }
            if (array_key_exists('created_at', $requestInfo)) {
                if (array_key_exists('from', $requestInfo['created_at'])) {
                    $query->where('created_at', '>=', $requestInfo['created_at']['from']);
                }
                if (array_key_exists('to', $requestInfo['created_at'])) {
                    $query->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
                }
                if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                    $query->whereBetween('created_at', [now()->addMonths(-1), now()]);
                }
            }
            if (array_key_exists('pay_date', $requestInfo)) {
                if (array_key_exists('from', $requestInfo['pay_date'])) {
                    $query->where('pay_date', '>=', $requestInfo['pay_date']['from']);
                }
                if (array_key_exists('to', $requestInfo['pay_date'])) {
                    $query->where('pay_date', '<=', $requestInfo['pay_date']['to']);
                }
                if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                    $query->whereBetween('pay_date', [now(), now()->addMonths(1)]);
                }
            }
            if (array_key_exists('days_late', $requestInfo)) {
                $query->whereHas('installments', function ($query) use ($requestInfo) {
                    $query->where('status', '!=', Config::get('constants.status.paid out'))->orWhereNull('status')->whereDate("due_date", "<=", Carbon::now()->subDays($requestInfo['days_late']));
                });
            }

            if (array_key_exists('company', $requestInfo)) {
                $query->whereHas('company', function ($query) use ($requestInfo) {
                    $query->where('id', $requestInfo['company']);
                });
            }

            if ($this->filterCanceled) {
                $query->withTrashed();
                $query->where('deleted_at', '!=', NULL);
            }
        });

        if (array_key_exists('cnab_date', $requestInfo)) {
            $query->whereHas('cnab_generated_installment', function ($cnabInstallment) use ($requestInfo) {
                $cnabInstallment->whereHas('generated_cnab', function ($cnabGenerated) use ($requestInfo) {
                    if (array_key_exists('from', $requestInfo['cnab_date'])) {
                        $cnabGenerated->where('file_date', '>=', $requestInfo['cnab_date']['from']);
                    }
                    if (array_key_exists('to', $requestInfo['cnab_date'])) {
                        $cnabGenerated->where('file_date', '<=', $requestInfo['cnab_date']['to']);
                    }
                    if (!array_key_exists('to', $requestInfo['cnab_date']) && !array_key_exists('from', $requestInfo['cnab_date'])) {
                        $cnabGenerated->whereBetween('file_date', [now(), now()->addMonths(1)]);
                    }
                });
            });
        }

        if (array_key_exists('extension_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['extension_date'])) {
                if (array_key_exists('from', $requestInfo['extension_date'])) {
                    $query->where('extension_date', '>=', $requestInfo['extension_date']['from']);
                }
                if (array_key_exists('to', $requestInfo['extension_date'])) {
                    $query->where('extension_date', '<=', $requestInfo['extension_date']['to']);
                }
                if (!array_key_exists('to', $requestInfo['extension_date']) && !array_key_exists('from', $requestInfo['extension_date'])) {
                    $query->whereBetween('extension_date', [now(), now()->addMonths(1)]);
                }
            }
        }
        return Utils::pagination($query, $requestInfo);
    }

    public function getAllPaymentRequestFinished($requestInfo)
    {
        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlowClean, $requestInfo);
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
        });

        return Utils::pagination($accountApproval
            ->with('purchase_order')
            ->with('purchase_order.installments')
            ->whereRelation('purchase_order', 'deleted_at', '=', null)
            ->where('status', 1), $requestInfo);
    }

    public function getAllApprovedPurchaseOrderForIntegration($requestInfo)
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
                ->with('user'),
            $requestInfo
        );
    }

    public function getCnabGenerate($requestInfo, $id)
    {
        return $this->cnabGenerated->with(['payment_requests', 'user'])->findOrFail($id);
    }
}

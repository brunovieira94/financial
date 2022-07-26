<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasInstallments;
use Illuminate\Http\Request;
use Config;
use Exception;

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
        $maxOrder = $this->approvalFlow->max('order');

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow, $requestInfo, ['order']);

        if (in_array($maxOrder, $approvalFlowUserOrder->pluck('order')->toArray())) {
            $accountsPayableApprovalFlow
                ->whereIn('status', [0, 2])
                ->where(function ($query) use ($approvalFlowUserOrder, $maxOrder) {
                    return $query->whereIn('order', $approvalFlowUserOrder->toArray())->orWhere('order', '>', $maxOrder);
                })
                ->whereRelation('payment_request', 'deleted_at', '=', null)
                ->with(['payment_request', 'approval_flow', 'reason_to_reject']);
        } else {
            $accountsPayableApprovalFlow->whereIn('order', $approvalFlowUserOrder->toArray())
                ->whereIn('status', [0, 2])
                ->whereRelation('payment_request', 'deleted_at', '=', null)
                ->with(['payment_request', 'approval_flow', 'reason_to_reject']);
        }

        $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
            if (array_key_exists('provider', $requestInfo)) {
                $query->where('provider_id', $requestInfo['provider']);
            }
            if (array_key_exists('net_value', $requestInfo)) {
                $query->where('net_value', $requestInfo['net_value']);
            }
            if (array_key_exists('company', $requestInfo)) {
                $query->where('company_id', $requestInfo['company']);
            }
            if (array_key_exists('cost_center', $requestInfo)) {
                $query->where('cost_center_id', $requestInfo['cost_center']);
            }
            if (array_key_exists('cpfcnpj', $requestInfo)) {
                $query->whereHas('provider', function ($query) use ($requestInfo) {
                    $query->where('cpf', $requestInfo['cpfcnpj'])->orWhere('cnpj', $requestInfo['cpfcnpj']);
                });
            }
            if (array_key_exists('chart_of_accounts', $requestInfo)) {
                $query->where('chart_of_account_id', $requestInfo['chart_of_accounts']);
            }
            if (array_key_exists('payment_request', $requestInfo)) {
                $query->where('id', $requestInfo['payment_request']);
            }
            if (array_key_exists('user', $requestInfo)) {
                $query->where('user_id', $requestInfo['user']);
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
                if (array_key_exists('from', $requestInfo['extension_date'])) {
                    $query->whereHas('installments', function ($installments) use ($requestInfo) {
                        if (array_key_exists('from', $requestInfo['extension_date'])) {
                            $installments->where('extension_date', '>=', $requestInfo['extension_date']['from']);
                        }
                        if (array_key_exists('to', $requestInfo['extension_date'])) {
                            $installments->where('extension_date', '<=', $requestInfo['extension_date']['to']);
                        }
                        if (!array_key_exists('to', $requestInfo['extension_date']) && !array_key_exists('from', $requestInfo['extension_date'])) {
                            $installments->whereBetween('extension_date', [now(), now()->addMonths(1)]);
                        }
                    });
                }
            }
            if (array_key_exists('days_late', $requestInfo)) {
                $query->whereHas('installments', function ($query) use ($requestInfo) {
                    $query->where('status', '!=', Config::get('constants.status.paid out'))->orWhereNull('status')->whereDate("due_date", "<=", Carbon::now()->subDays($requestInfo['days_late']));
                });
            }
        });

        if (array_key_exists('approval_order', $requestInfo)) {
            $accountsPayableApprovalFlow->where('order', $requestInfo['approval_order']);
        }

        if (array_key_exists('status', $requestInfo)) {
            $accountsPayableApprovalFlow->where('status', $requestInfo['status']);
        }

        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'accounts_payable_approval_flows.id';
        $accountsPayableApprovalFlows = Utils::pagination($accountsPayableApprovalFlow, $requestInfo);

        foreach ($accountsPayableApprovalFlows as  $accountsPayableApprovalFlow) {
            if ($accountsPayableApprovalFlow['payment_request'] != null) {
                foreach ($accountsPayableApprovalFlow['payment_request']['purchase_order'] as $purchaseOrder) {
                    foreach ($purchaseOrder->purchase_order_installments as $key => $installment) {
                        $installment = [
                            'id' => $installment->installment_purchase->id,
                            'amount_received' => $installment->amount_received,
                            'purchase_order_id' => $installment->installment_purchase->purchase_order_id,
                            'parcel_number' => $installment->installment_purchase->parcel_number,
                            'portion_amount' => $installment->installment_purchase->portion_amount,
                            'due_date' => $installment->installment_purchase->due_date,
                            'note' => $installment->installment_purchase->note,
                            'percentage_discount' => $installment->installment_purchase->percentage_discount,
                            'money_discount' => $installment->installment_purchase->money_discount,
                            'invoice_received' => $installment->installment_purchase->invoice_received,
                            'invoice_paid' => $installment->installment_purchase->invoice_paid,
                            'payment_request_id' => $installment->installment_purchase->payment_request_id,
                            'amount_paid' => $installment->installment_purchase->amount_paid,
                        ];
                        $purchaseOrder->purchase_order_installments[$key] = $installment;
                    }
                }
            }
        }
        return $accountsPayableApprovalFlows;
    }

    public function approveAccount($id)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->with('payment_request')->findOrFail($id);

        if ($this->approvalFlow
            ->where('order', $accountApproval->order)
            ->where('role_id', auth()->user()->role_id)
            ->doesntExist()
        ) {
            return response()->json([
                'error' => 'Não é permitido a esse usuário aprovar a conta ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
            ], 422);
        }

        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = 0;

        if ($accountApproval->order >= $maxOrder) {
            $accountApproval->status = Config::get('constants.status.approved');
        } else {
            $accountApproval->order += 1;
        }

        $accountApproval->reason = null;
        $accountApproval->reason_to_reject_id = null;
        $accountApproval->save();
        return response()->json([
            'Sucesso' => 'Conta aprovada',
        ], 200);
    }

    public function approveManyAccounts($requestInfo)
    {
        if (array_key_exists('ids', $requestInfo)) {
            if (array_key_exists('reprove', $requestInfo) && $requestInfo['reprove'] == true) {
                foreach ($requestInfo['ids'] as $value) {
                    $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($value);
                    $maxOrder = $this->approvalFlow->max('order');
                    $accountApproval->status = Config::get('constants.status.disapproved');

                    if ($this->approvalFlow
                        ->where('order', $accountApproval->order)
                        ->where('role_id', auth()->user()->role_id)
                        ->doesntExist()
                    ) {
                        return response()->json([
                            'error' => 'Não é permitido a esse usuário reprovar ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
                        ], 422);
                    }

                    if ($accountApproval->order > $maxOrder) {
                        $accountApproval->order = Config::get('constants.status.open');
                    } else if ($accountApproval->order != 0) {
                        $accountApproval->order -= 1;
                    }
                    $accountApproval->reason = null;
                    $accountApproval->reason_to_reject_id = null;
                    $accountApproval->fill($requestInfo)->save();
                }
                return response()->json([
                    'Sucesso' => 'Contas reprovadas',
                ], 200);
            } else {
                foreach ($requestInfo['ids'] as $value) {
                    $accountApproval = $this->accountsPayableApprovalFlow->with('payment_request')->findOrFail($value);
                    $maxOrder = $this->approvalFlow->max('order');
                    $accountApproval->status = 0;

                    if ($this->approvalFlow
                        ->where('order', $accountApproval->order)
                        ->where('role_id', auth()->user()->role_id)
                        ->doesntExist()
                    ) {
                        return response()->json([
                            'error' => 'Não é permitido a esse usuário aprovar ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
                        ], 422);
                    }

                    if ($accountApproval->order >= $maxOrder) {
                        $accountApproval->status = Config::get('constants.status.approved');
                    } else {
                        $accountApproval->order += 1;
                    }
                    $accountApproval->reason = null;
                    $accountApproval->reason_to_reject_id = null;
                    $accountApproval->save();
                }
                return response()->json([
                    'Sucesso' => 'Contas aprovadas',
                ], 200);
            }
        } else {
            return response()->json([
                'error' => 'Nenhuma conta selecionada',
            ], 422);
        }
    }

    public function reproveAccount($id, Request $request)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');

        if ($this->approvalFlow
            ->where('order', $accountApproval->order)
            ->where('role_id', auth()->user()->role_id)
            ->doesntExist()
        ) {
            return response()->json([
                'error' => 'Não é permitido a esse usuário reprovar a conta ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
            ], 422);
        }

        $accountApproval->status = Config::get('constants.status.disapproved');

        if ($accountApproval->order > $maxOrder) {
            $accountApproval->order = Config::get('constants.status.open');
        } else if ($accountApproval->order != 0) {
            $accountApproval->order -= 1;
        }
        $accountApproval->reason = null;
        $accountApproval->reason_to_reject_id = null;
        $accountApproval->fill($request->all())->save();
        return response()->json([
            'Sucesso' => 'Conta reprovada',
        ], 200);
    }

    public function cancelAccount($id, Request $request)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->with('payment_request')->findOrFail($id);
        $accountApproval->status = Config::get('constants.status.canceled');
        $accountApproval->fill($request->all())->save();
        activity()->disableLogging();
        PaymentRequest::findOrFail($accountApproval->payment_request->id)->delete();
        activity()->enableLogging();
        return response()->json([
            'Sucesso' => 'Conta cancelada',
        ], 200);
    }
}

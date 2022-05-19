<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
use App\Models\ApprovalFlow;
use App\Models\PaymentRequest;
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

        $accountsPayableApprovalFlow = Utils::search($this->accountsPayableApprovalFlow, $requestInfo, ['order']);

        $accountsPayableApprovalFlow->whereIn('order', $approvalFlowUserOrder->toArray())
            ->where('status', 0)
            ->whereRelation('payment_request', 'deleted_at', '=', null)
            ->with(['payment_request', 'approval_flow', 'reason_to_reject']);


        if (array_key_exists('provider', $requestInfo)) {
            $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
                $query->where('provider_id', $requestInfo['provider']);
            });
        }

        if (array_key_exists('provider', $requestInfo)) {
            $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
                $query->where('provider_id', $requestInfo['provider']);
            });
        }

        if (array_key_exists('company', $requestInfo)) {
            $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
                $query->where('company_id', $requestInfo['company']);
            });
        }

        if (array_key_exists('cost_center', $requestInfo)) {
            $accountsPayableApprovalFlow->whereHas('payment_request', function ($query) use ($requestInfo) {
                $query->where('cost_center_id', $requestInfo['cost_center']);
            });
        }

        if (array_key_exists('approval_order', $requestInfo)) {
            $accountsPayableApprovalFlow->where('order', $requestInfo['approval_order']);
        }

        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'accounts_payable_approval_flows.id';
        return Utils::pagination($accountsPayableApprovalFlow, $requestInfo);
    }

    public function approveAccount($id)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->with('payment_request')->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = 0;

        if ($accountApproval->order >= $maxOrder) {
            if ($accountApproval->payment_request->group_form_payment_id != 1) {
                if ($accountApproval->payment_request->bank_account_provider_id == null) {
                    return response()->json([
                        'error' => 'O banco do fornecedor não foi informado.',
                    ], 422);
                }
            } else if ($accountApproval->payment_request->group_form_payment_id == 1) {
                if ($accountApproval->payment_request->bar_code == null) {
                    return response()->json([
                        'error' => 'O código de barras não foi informado.',
                    ], 422);
                }
            } //elseif($accountApproval->payment_request->payment_type == 1){
            //  if (!$accountApproval->payment_request->provider->accept_billet_payment){
            //      if(is_null($accountApproval->payment_request->invoice_number)){
            //          return response()->json([
            //              'error' => 'A nota fiscal não foi informada.',
            //          ], 422);
            //      }
            //  }
            //}

            $accountApproval->status = Config::get('constants.status.approved');
            $accountApproval->order += 1;
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

                    if ($accountApproval->order > $maxOrder) {
                        $accountApproval->order = Config::get('constants.status.open');
                    } else if ($accountApproval->order != 0) {
                        $accountApproval->order -= 1;
                    }
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

                    if ($accountApproval->order >= $maxOrder) {
                        if ($accountApproval->payment_request->group_form_payment_id != 1) {
                            if ($accountApproval->payment_request->bank_account_provider_id == null) {
                                return response()->json([
                                    'error' => 'O banco do fornecedor não foi informado. Id: ' . $value,
                                ], 422);
                            }
                        } else if ($accountApproval->payment_request->group_form_payment_id == 1) {
                            if ($accountApproval->payment_request->bar_code == null) {
                                return response()->json([
                                    'error' => 'O código de barras não foi informado. Id: ' . $value,
                                ], 422);
                            }
                        }
                        $accountApproval->status = Config::get('constants.status.approved');
                        $accountApproval->order += 1;
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
                'Erro' => 'Nenhuma Conta Selecionada',
            ], 422);
        }
    }

    public function reproveAccount($id, Request $request)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = Config::get('constants.status.disapproved');

        if ($accountApproval->order > $maxOrder) {
            $accountApproval->order = Config::get('constants.status.open');
        } else if ($accountApproval->order != 0) {
            $accountApproval->order -= 1;
        }
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

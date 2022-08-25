<?php

namespace App\Services;

use App\Http\Resources\ApprovalFlowByUserCollection;
use App\Http\Resources\ApprovalFlowByUserResource;
use App\Http\Resources\PaymentRequestCollection;
use App\Http\Resources\reports\ApprovalFlowByUserResource as ReportsApprovalFlowByUserResource;
use App\Http\Resources\reports\RouteApprovalFlowByUserResource;
use App\Http\Resources\RouteApprovalFlowByUserCollection;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\ApprovalFlow;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Models\PaymentRequestHasInstallments;
use Illuminate\Http\Request;
use Config;
use Exception;

class ApprovalFlowByUserService
{
    private $accountsPayableApprovalFlow;
    private $approvalFlow;
    private $accountsPayableApprovalFlowClean;
    private $paymentRequestClean;

    private $paymentRequestCleanWith = ['installments', 'company', 'provider', 'cost_center', 'approval.approval_flow', 'currency', 'cnab_payment_request.cnab_generated'];

    public function __construct(PaymentRequestClean $paymentRequestClean, AccountsPayableApprovalFlowClean $accountsPayableApprovalFlowClean, AccountsPayableApprovalFlow $accountsPayableApprovalFlow, ApprovalFlow $approvalFlow)
    {
        $this->accountsPayableApprovalFlow = $accountsPayableApprovalFlow;
        $this->approvalFlow = $approvalFlow;
        $this->accountsPayableApprovalFlowClean = $accountsPayableApprovalFlowClean;
        $this->paymentRequestClean = $paymentRequestClean;
    }

    public function getAllAccountsForApproval($requestInfo)
    {
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $paymentRequest = Utils::search($this->paymentRequestClean, $requestInfo, ['order']);
        $paymentRequest->whereHas('approval', function ($query) use ($approvalFlowUserOrder) {
            $query->whereIn('order', $approvalFlowUserOrder->toArray())
                ->whereIn('status', [0, 2])
                ->where('deleted_at', '=', null);
        });
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);
        $paymentRequest = $paymentRequest->with($this->paymentRequestCleanWith);
        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'id';
        return RouteApprovalFlowByUserResource::collection(Utils::pagination($paymentRequest, $requestInfo)); //;
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
        PaymentRequest::withoutGlobalScopes()->findOrFail($accountApproval->payment_request->id)->delete();
        activity()->enableLogging();
        return response()->json([
            'Sucesso' => 'Conta cancelada',
        ], 200);
    }
}

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
use App\Models\User;
use App\Models\UserHasCostCenter;
use App\Models\UserHasPaymentRequest;
use Illuminate\Http\Request;
use Config;
use CreateUserHasPaymentRequest;
use Exception;
use Illuminate\Support\Facades\DB;
use Response;

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
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order', 'group_approval_flow_id']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $paymentRequest = Utils::search($this->paymentRequestClean, $requestInfo, ['order']);
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);

        $paymentRequest->whereHas('approval', function ($query) use ($approvalFlowUserOrder) {
            //$query->whereIn('order', $approvalFlowUserOrder->pluck('order')->toArray())
            $query->whereIn('status', [0, 2])
                ->where('deleted_at', '=', null);
        });
        $idsPaymentRequestOrder = [];
        foreach ($approvalFlowUserOrder as $approvalOrder) {
            $accountApprovalFlow = AccountsPayableApprovalFlowClean::where('order', $approvalOrder['order'])->with('payment_request');
            $accountApprovalFlow = $accountApprovalFlow->whereHas('payment_request', function ($query) use ($approvalOrder) {
                $query->where('group_approval_flow_id', $approvalOrder['group_approval_flow_id']);
            })->get('payment_request_id');
            $idsPaymentRequestOrder = array_merge($idsPaymentRequestOrder, $accountApprovalFlow->pluck('payment_request_id')->toArray());
        }
        $paymentRequest = $paymentRequest->whereIn('id', $idsPaymentRequestOrder);
        $multiplePaymentRequest = UserHasPaymentRequest::where('user_id', auth()->user()->id)->where('status', 0)->get('payment_request_id');
        //$paymentRequest = $paymentRequest->orWhere(function ($query) use ($multiplePaymentRequest, $requestInfo) {
        $ids = $multiplePaymentRequest->pluck('payment_request_id')->toArray();
        $paymentRequestMultiple = PaymentRequest::withoutGlobalScopes()->whereIn('id', $ids);
        $paymentRequestMultiple = Utils::baseFilterReportsPaymentRequest($paymentRequestMultiple, $requestInfo);
        $paymentRequestMultiple->get('id');
        $ids = $paymentRequestMultiple->pluck('id')->toArray();
        //union ids payment request
        $paymentRequestIDs = $paymentRequest->get('id');
        $paymentRequestIDs = $paymentRequest->pluck('id')->toArray();
        $ids = array_merge($ids, $paymentRequestIDs);
        $paymentRequest = $this->paymentRequestClean->withoutGlobalScopes()->whereIn('id', $ids)->with($this->paymentRequestCleanWith);
        $requestInfo['orderBy'] = $requestInfo['orderBy'] ?? 'id';
        return RouteApprovalFlowByUserResource::collection(Utils::pagination($paymentRequest, $requestInfo)); //;
    }

    public function approveAccount($id)
    {
        $requestInfo['ids'] = [$id];
        $requestInfo['reprove'] = false;
        return $this->approveManyAccounts($requestInfo);
    }

    public function approveManyAccounts($requestInfo)
    {
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['group_approval_flow_id']);
        if (array_key_exists('ids', $requestInfo)) {
            if (array_key_exists('reprove', $requestInfo) && $requestInfo['reprove'] == true) {
                foreach ($requestInfo['ids'] as $value) {
                    $accountApproval = $this->accountsPayableApprovalFlow->findOrFail($value);
                    $maxOrder = $this->approvalFlow->where('group_approval_flow_id', $accountApproval->payment_request->group_approval_flow_id)->max('order');
                    $accountApproval->status = Config::get('constants.status.disapproved');
                    if ($this->paymentRequestAddedUser($accountApproval->payment_request->id)) {
                        $this->updatePaymentRequestUserStatus($accountApproval->payment_request->id, null, 2);
                        $this->approveOrDisapprove($accountApproval, false, $maxOrder, $requestInfo);
                    } else {
                        if ($this->approvalFlow
                            ->where('order', $accountApproval->order)
                            ->where('role_id', auth()->user()->role_id)
                            ->whereIn('group_approval_flow_id', $approvalFlowUserOrder->pluck('group_approval_flow_id'))
                            ->doesntExist()
                        ) {
                            return response()->json([
                                'error' => 'Não é permitido a esse usuário reprovar ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
                            ], 422);
                        }
                        $this->approveOrDisapprove($accountApproval, false, $maxOrder, $requestInfo);
                    }
                }
                return response()->json([
                    'Sucesso' => 'Contas reprovadas',
                ], 200);
            } else {
                foreach ($requestInfo['ids'] as $value) {
                    $accountApproval = $this->accountsPayableApprovalFlow->with('payment_request')->findOrFail($value);
                    $maxOrder = $this->approvalFlow->where('group_approval_flow_id', $accountApproval->payment_request->group_approval_flow_id)->max('order');
                    $accountApproval->status = 0;
                    if ($this->paymentRequestAddedUser($accountApproval->payment_request->id)) {
                        if (!$this->paymentRequestOnlyApprove($accountApproval->payment_request->id)) {
                            $this->approveOrDisapprove($accountApproval, true, $maxOrder, $requestInfo);
                            $this->updatePaymentRequestUserStatus($accountApproval->payment_request->id, auth()->user()->id, 1);
                        } else {
                            $accountApproval->action = 1;
                            $accountApproval->save();
                            $this->updatePaymentRequestUserStatus($accountApproval->payment_request->id, auth()->user()->id, 1);
                        }
                    } else {
                        if ($this->approvalFlow
                            ->where('order', $accountApproval->order)
                            ->where('role_id', auth()->user()->role_id)
                            ->whereIn('group_approval_flow_id', $approvalFlowUserOrder->pluck('group_approval_flow_id'))
                            ->doesntExist()
                        ) {
                            return response()->json([
                                'error' => 'Não é permitido a esse usuário aprovar ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
                            ], 422);
                        }
                        $this->approveOrDisapprove($accountApproval, true, $maxOrder, $requestInfo);
                    }
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
        $requestInfo = $request->all();
        $requestInfo['ids'] = [$id];
        $requestInfo['reprove'] = true;
        return $this->approveManyAccounts($requestInfo);
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

    public function multipleApproval($requestInfo)
    {
        if (auth()->user()->role->transfer_approval) {
            foreach ($requestInfo['payment_requests'] as $idPaymentRequest) {
                //gerar log de aprovação da conta
                $accountApprovalFlow = AccountsPayableApprovalFlow::where('payment_request_id', $idPaymentRequest)->first();
                $accountApprovalFlow->status = 0;
                $accountApprovalFlow->action = 1;
                $accountApprovalFlow->save();
                $nameUsers = '';
                $concatenate = false;
                foreach ($requestInfo['users'] as $idUser) {
                    if (auth()->user()->id != $idUser) {
                        if (!UserHasPaymentRequest::where('user_id', $idUser)->where('payment_request_id', $idPaymentRequest)->where('status', 0)->exists()) {
                            UserHasPaymentRequest::create([
                                'user_id' => $idUser,
                                'payment_request_id' => $idPaymentRequest,
                                'status' => 0
                            ]);
                            if (!$concatenate) {
                                $nameUsers = User::findOrFail($idUser)->name;
                                $concatenate = true;
                            } else {
                                $nameUsers = $nameUsers . ', ' . User::findOrFail($idUser)->name;
                            }
                        }
                    }
                }
                $accountsPayableApprovalFlow = $this->accountsPayableApprovalFlowClean->where('payment_request_id', $idPaymentRequest)->first();
                $accountsPayableApprovalFlow->status = Config::get('constants.status.multiple approval');
                $accountsPayableApprovalFlow->reason = $nameUsers;
                $accountsPayableApprovalFlow->save();
                if (UserHasPaymentRequest::where('payment_request_id', $idPaymentRequest)->where('user_id', auth()->user()->id)->where('status', 0)->exists()) {
                    $userHasPaymentRequest = UserHasPaymentRequest::where('payment_request_id', $idPaymentRequest)->where('user_id', auth()->user()->id)->where('status', 0)->first();
                    $userHasPaymentRequest->status = 1;
                    $userHasPaymentRequest->save();
                }
            }
            return true;
        } else {
            return Response()->json(['error' => 'O perfil deste usuário não pode transferir e/ou solicitar dupla aprovação'], 422);
        }
    }

    public function transferApproval($requestInfo)
    {
        if (auth()->user()->role->transfer_approval) {
            foreach ($requestInfo['payment_requests'] as $idPaymentRequest) {
                if (UserHasPaymentRequest::where('payment_request_id', $idPaymentRequest)->where('user_id', auth()->user()->id)->where('status', 0)->exists()) {
                    $userHasPaymentRequest = UserHasPaymentRequest::where('payment_request_id', $idPaymentRequest)->where('user_id', auth()->user()->id)->where('status', 0)->first();
                    $userHasPaymentRequest->status = 1;
                    $userHasPaymentRequest->save();
                }
                if (!UserHasPaymentRequest::where('user_id', $requestInfo['user'])->where('payment_request_id', $idPaymentRequest)->where('status', 0)->exists()) {
                    if (auth()->user()->id != $requestInfo['user']) {
                        UserHasPaymentRequest::create([
                            'user_id' => $requestInfo['user'],
                            'payment_request_id' => $idPaymentRequest,
                            'status' => 0
                        ]);
                        $accountsPayableApprovalFlow = $this->accountsPayableApprovalFlowClean->where('payment_request_id', $idPaymentRequest)->first();
                        $accountsPayableApprovalFlow->status = Config::get('constants.status.transfer approval');
                        $accountsPayableApprovalFlow->reason = User::findOrFail($requestInfo['user'])->name;
                        $accountsPayableApprovalFlow->save();
                    }
                }
            }
            return true;
        } else {
            return Response()->json(['error' => 'O perfil deste usuário não pode transferir e/ou solicitar dupla aprovação'], 422);
        }
    }

    public function paymentRequestAddedUser($idPaymentRequest)
    {
        return UserHasPaymentRequest::where('payment_request_id', $idPaymentRequest)->where('user_id', auth()->user()->id)->where('status', 0)->exists();
    }
    public function paymentRequestOnlyApprove($idPaymentRequest)
    {
        return UserHasPaymentRequest::where('payment_request_id', $idPaymentRequest)->whereNotIn('user_id', [auth()->user()->id])->where('status', 0)->exists();
    }

    public function updatePaymentRequestUserStatus($idPaymentRequest, $idUser, $status)
    {
        if ($idUser != null) {
            if (UserHasPaymentRequest::where('user_id', $idUser)->where('payment_request_id', $idPaymentRequest)->where('status', 0)->exists()) {
                $userHasPaymentRequest = UserHasPaymentRequest::where('user_id', $idUser)->where('payment_request_id', $idPaymentRequest)->where('status', 0)->first();
                $userHasPaymentRequest->status = $status;
                $userHasPaymentRequest->save();
            }
        } else {
            if (UserHasPaymentRequest::where('payment_request_id', $idPaymentRequest)->where('status', 0)->exists()) {
                $userHasPaymentRequests = UserHasPaymentRequest::where('payment_request_id', $idPaymentRequest)->where('status', 0)->get();
                foreach ($userHasPaymentRequests as $userHasPaymentRequest) {
                    $userHasPaymentRequest = UserHasPaymentRequest::findOrFail($userHasPaymentRequest['id']);
                    $userHasPaymentRequest->status = $status;
                    $userHasPaymentRequest->save();
                }
            }
        }
    }
    public function approveOrDisapprove($accountApproval, $approve, $maxOrder, $requestInfo)
    {
        if ($approve) {
            if ($accountApproval->order >= $maxOrder) {
                $accountApproval->status = Config::get('constants.status.approved');
            } else {
                $accountApproval->order += 1;
                $accountApproval->status = Config::get('constants.status.open');
            }
            $accountApproval->action = 1;
            $accountApproval->save();
        } else {
            if ($accountApproval->order > $maxOrder) {
                $accountApproval->order = Config::get('constants.status.open');
            } else if ($accountApproval->order != 0) {
                $accountApproval->order -= 1;
            }
            $accountApproval->action = 2;
            $accountApproval->fill($requestInfo)->save();
        }
    }
}

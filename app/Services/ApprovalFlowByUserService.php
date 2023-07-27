<?php

namespace App\Services;

use App\Http\Resources\reports\RouteApprovalFlowByUserResource;
use App\Http\Resources\RouteApprovalFlowByUserCollection;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowClean;
use App\Models\ApprovalFlow;
use App\Models\ApprovalLog;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Models\ReasonToReject;
use App\Models\User;
use App\Models\UserHasPaymentRequest;
use Illuminate\Http\Request;
use Config;
use Illuminate\Support\Facades\Redis;

class ApprovalFlowByUserService
{
    private $accountsPayableApprovalFlow;
    private $approvalFlow;
    private $accountsPayableApprovalFlowClean;
    private $paymentRequestClean;
    private $user;
    private $alterOrder = false;
    private $order = null;

    private $paymentRequestCleanWith = ['installments', 'company', 'provider', 'cost_center', 'approval.approval_flow', 'currency', 'cnab_payment_request.cnab_generated'];

    public function __construct(User $user, PaymentRequestClean $paymentRequestClean, AccountsPayableApprovalFlowClean $accountsPayableApprovalFlowClean, AccountsPayableApprovalFlow $accountsPayableApprovalFlow, ApprovalFlow $approvalFlow)
    {
        $this->accountsPayableApprovalFlow = $accountsPayableApprovalFlow;
        $this->approvalFlow = $approvalFlow;
        $this->accountsPayableApprovalFlowClean = $accountsPayableApprovalFlowClean;
        $this->paymentRequestClean = $paymentRequestClean;
        $this->user = $user;
    }

    public function getAllAccountsForApproval($requestInfo)
    {
        auth()->user()->id = auth()->user()->logged_user_id == null ? auth()->user()->id : auth()->user()->logged_user_id;
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order', 'group_approval_flow_id']);

        if (!$approvalFlowUserOrder)
            return RouteApprovalFlowByUserResource::collection(Utils::pagination($this->paymentRequestClean->where('id', -1), $requestInfo));

        $paymentRequest = Utils::search($this->paymentRequestClean, $requestInfo, ['order']);
        $paymentRequest = Utils::baseFilterReportsPaymentRequest($paymentRequest, $requestInfo);

        $paymentRequest->whereHas('approval', function ($query) use ($requestInfo) {
            $arrayStatus = Utils::statusApprovalFlowRequest($requestInfo);
            $query->whereIn('status', $arrayStatus)
                ->where('deleted_at', '=', null);
        });
        $idsPaymentRequestOrder = [];
        foreach ($approvalFlowUserOrder as $approvalOrder) {
            $accountApprovalFlow = AccountsPayableApprovalFlowClean::where('order', $approvalOrder['order'])->with('payment_request');
            $accountApprovalFlow = $accountApprovalFlow->whereHas('payment_request', function ($query) use ($approvalOrder) {
                $query->where('group_approval_flow_id', $approvalOrder['group_approval_flow_id']);
            })->whereIn('status', [0, 2, 8, 9])->get('payment_request_id');
            $idsPaymentRequestOrder = array_merge($idsPaymentRequestOrder, $accountApprovalFlow->pluck('payment_request_id')->toArray());
        }
        $paymentRequest = $paymentRequest->whereIn('id', $idsPaymentRequestOrder);
        $multiplePaymentRequest = UserHasPaymentRequest::where('user_id', auth()->user()->id)->where('status', 0)->get('payment_request_id');
        //$paymentRequest = $paymentRequest->orWhere(function ($query) use ($multiplePaymentRequest, $requestInfo) {
        $ids = $multiplePaymentRequest->pluck('payment_request_id')->toArray();
        $paymentRequestMultiple = PaymentRequestClean::withoutGlobalScopes()->whereIn('id', $ids);
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

    public function approveAccount($id, $requestInfo)
    {
        $order = null;
        if (array_key_exists('order', $requestInfo)) {
            $order = $requestInfo['order'];
        }
        $requestInfo['ids'][0] = [
            'id' => $id,
            'order' => $order
        ];
        $requestInfo['reprove'] = false;
        return $this->approveManyAccounts($requestInfo);
    }

    public function approveManyAccounts($requestInfo)
    {
        if (array_key_exists('ids', $requestInfo)) {
            $dataAnalysis = [];
            if (array_key_exists('reprove', $requestInfo) && $requestInfo['reprove'] == true) {
                foreach ($requestInfo['ids'] as $value) {
                    $value = self::updateOrder($value);
                    if (!Redis::exists($value)) {
                        Redis::set($value, 'payment-request', 'EX', '10');
                        $paymentRequest = $this->paymentRequestClean->with('approval')->withoutGlobalScopes()->findOrFail($value);
                        $maxOrder = $this->approvalFlow->where('group_approval_flow_id', $paymentRequest->group_approval_flow_id)->max('order');
                        $paymentRequest->approval()->update(['status' => Config::get('constants.status.disapproved')]);
                        if ($this->paymentRequestAddedUser($paymentRequest->id)) {
                            $this->updatePaymentRequestUserStatus($paymentRequest->id, null, 2);
                            $this->approveOrDisapprove($paymentRequest, false, $maxOrder, $requestInfo);
                        } else {
                            if ($this->approvalFlow
                                ->where('order', $paymentRequest->approval->order)
                                ->where('role_id', auth()->user()->role_id)
                                ->where('group_approval_flow_id', $paymentRequest->group_approval_flow_id)
                                ->doesntExist()
                            ) {
                                Redis::del('h', $value);
                                return response()->json([
                                    'error' => 'Não é permitido a este usuário provar ID: ' . $paymentRequest->id . ', a conta já foi reprovada',
                                ], 422);
                            }
                            $this->approveOrDisapprove($paymentRequest, false, $maxOrder, $requestInfo);
                        }
                        $dataAnalysis[] = [
                            'payment-request' => $paymentRequest->id,
                            'order' => $paymentRequest->approval->order
                        ];
                    }
                }
                self::createAnalysisLog($requestInfo, $dataAnalysis);
                return response()->json([
                    'Sucesso' => 'Contas reprovadas',
                ], 200);
            } else {
                foreach ($requestInfo['ids'] as $value) {
                    $value = self::updateOrder($value);
                    if (!Redis::exists($value)) {
                        Redis::set($value, 'payment-request', 'EX', '10');
                        $paymentRequest = $this->paymentRequestClean->with('approval')->withoutGlobalScopes()->findOrFail($value);
                        $maxOrder = $this->approvalFlow->where('group_approval_flow_id', $paymentRequest->group_approval_flow_id)->max('order');
                        $paymentRequest->approval()->update(['status' => 0]);
                        if ($this->paymentRequestAddedUser($paymentRequest->id)) {
                            if (!$this->paymentRequestOnlyApprove($paymentRequest->id)) {
                                $this->approveOrDisapprove($paymentRequest, true, $maxOrder, $requestInfo);
                                $this->updatePaymentRequestUserStatus($paymentRequest->id, auth()->user()->id, 1);
                            } else {
                                $paymentRequest->approval()->update(['action' => 1]);
                                $description = isset($requestInfo['reason']) ? $requestInfo['reason'] : null;
                                Utils::createLogApprovalFlowLogPaymentRequest($paymentRequest->id, 'approved', null, $description, $paymentRequest->approval->order, auth()->user()->id, null, $paymentRequest->approval->order);
                                $this->updatePaymentRequestUserStatus($paymentRequest->id, auth()->user()->id, 1);
                            }
                        } else {
                            if ($this->approvalFlow
                                ->where('order', $paymentRequest->approval->order)
                                ->where('role_id', auth()->user()->role_id)
                                ->where('group_approval_flow_id', $paymentRequest->group_approval_flow_id)
                                ->doesntExist()
                            ) {
                                Redis::del('h', $value);
                                return response()->json([
                                    'error' => 'Não é permitido a este usuário provar ID: ' . $paymentRequest->id . ', a conta já foi aprovada',
                                ], 422);
                            }
                            $this->approveOrDisapprove($paymentRequest, true, $maxOrder, $requestInfo);
                        }
                    }
                    $dataAnalysis[] = [
                        'payment-request' => $paymentRequest->id,
                        'order' => $paymentRequest->approval->order
                    ];
                }
                self::createAnalysisLog($requestInfo, $dataAnalysis);
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

    public function reproveAccount($id, $requestInfo)
    {
        $order = null;
        if (array_key_exists('order', $requestInfo)) {
            $order = $requestInfo['order'];
        }
        $requestInfo['ids'][0] = [
            'id' => $id,
            'order' => $order
        ];
        $requestInfo['reprove'] = true;
        return $this->approveManyAccounts($requestInfo);
    }

    public function cancelAccount($id, Request $request)
    {
        $accountApproval = $this->accountsPayableApprovalFlow->where('payment_request_id', $id)->first();
        $accountApproval->status = Config::get('constants.status.canceled');
        $accountApproval->fill($request->all())->save();
        activity()->disableLogging();
        PaymentRequest::withoutGlobalScopes()->findOrFail($accountApproval->payment_request_id)->delete();
        $requestInfo = $request->all();
        $description = isset($requestInfo['reason']) ? $requestInfo['reason'] : null;
        $motive = isset($requestInfo['reason_to_reject_id']) ? ReasonToReject::findOrFail($request->all()['reason_to_reject_id'])->title : null;
        Utils::createLogApprovalFlowLogPaymentRequest($id, 'deleted', $motive, $description, $accountApproval->order, auth()->user()->id, null, null, $accountApproval->order);
        activity()->enableLogging();
        return response()->json([
            'Sucesso' => 'Conta cancelada',
        ], 200);
    }

    public function multipleApproval($requestInfo)
    {
        if (auth()->user()->role->transfer_approval) {
            foreach ($requestInfo['users'] as $idUser) {
                $user = User::findOrFail($idUser);
                if ($user->status != 0) {
                    return Response()->json(
                        [
                            'error' => 'O usuário ' . $user->name . ' não está ativo no sistema.'
                        ],
                        422
                    );
                }
            }
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
                Utils::createLogApprovalFlowLogPaymentRequest($idPaymentRequest, 'multiple-approval', null, null, $accountsPayableApprovalFlow->order, auth()->user()->id, $nameUsers, null, $accountsPayableApprovalFlow->order);
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
            $user = User::findOrFail($requestInfo['user']);
            if ($user->status != 0) {
                return Response()->json(
                    [
                        'error' => 'O usuário ' . $user->name . ' não está ativo no sistema.'
                    ],
                    422
                );
            }
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
                        $userTransfer = User::findOrFail($requestInfo['user'])->name;
                        $accountsPayableApprovalFlow->reason = $userTransfer;
                        Utils::createLogApprovalFlowLogPaymentRequest($idPaymentRequest, 'transfer-approval', null, null, $accountsPayableApprovalFlow->order, auth()->user()->id, $userTransfer, null, $accountsPayableApprovalFlow->order);
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
    public function approveOrDisapprove($paymentRequest, $approve, $maxOrder, $requestInfo)
    {
        if (array_key_exists('order', $requestInfo)) {
            unset($requestInfo['order']);
        }

        if ($paymentRequest->approval->status = 1 or $paymentRequest->approval->status = 2) {
            if ($approve) {
                $description = isset($requestInfo['reason']) ? $requestInfo['reason'] : null;
                $oldOrder = $paymentRequest->approval->order;
                if ($paymentRequest->approval->order >= $maxOrder) {
                    $paymentRequest->approval()->update([
                        'status' => Config::get('constants.status.approved'),
                        'action' => 1,
                    ]);
                } else {
                    $paymentRequest->approval()->update([
                        'status' => Config::get('constants.status.open'),
                        'order' => $paymentRequest->approval->order = $this->alterOrder == false ? ($paymentRequest->approval->order + 1) : $this->order,
                        'action' => 1,
                    ]);
                }
                Utils::createLogApprovalFlowLogPaymentRequest($paymentRequest->id, 'approved', null, $description, $oldOrder, auth()->user()->id, null, null, $paymentRequest->approval->order);
            } else {
                $description = isset($requestInfo['reason']) ? $requestInfo['reason'] : null;
                $reason = isset($requestInfo['reason_to_reject_id']) ? ReasonToReject::findOrFail($requestInfo['reason_to_reject_id'])->title : null;
                $oldOrder = $paymentRequest->approval->order;
                if ($paymentRequest->approval->order > $maxOrder) {
                    $paymentRequest->approval()->update([
                        'order' => Config::get('constants.status.open'),
                        'action' => 2,
                    ]);
                } else if ($paymentRequest->approval->order != 0) {
                    $paymentRequest->approval()->update([
                        'order' => $this->alterOrder == false ? ($paymentRequest->approval->order - 1) : $this->order,
                        'action' => 2,
                    ]);
                }
                $approvalData = array_intersect_key($requestInfo, array_flip($paymentRequest->approval->getFillable()));
                $paymentRequest->approval()->update($approvalData);
                Utils::createLogApprovalFlowLogPaymentRequest($paymentRequest->id, 'rejected', $reason, $description, $oldOrder, auth()->user()->id, null, null, $paymentRequest->approval->order);
            }
        }
        $this->alterOrder = false;
        Redis::del('h', $paymentRequest->id);
        //$this->notifyUsers($accountApproval, $notify, $maxOrder);
    }

    public function notifyUsers($accountApproval, $notify, $maxOrder)
    {
        /*
       solve problem notification snapshot approval account
        if ($notify) {
            if (NotificationCatalog::where(['type' => 'payment-request-to-approve', 'active' => true, 'schedule' => 0])->exists()) {
                $approvalFlowOrders = $this->approvalFlow
                    ->where('group_approval_flow_id', $accountApproval->payment_request->group_approval_flow_id)
                    ->where('order', $accountApproval->order)
                    ->get('role_id')->pluck('role_id');

                $usersId = Utils::userWithActiveNotification('payment-request-to-approve', false);

                $usersNotify = $this->user->with((['cost_center' => function ($query) use ($accountApproval) {
                    $query->where('cost_center_id', $accountApproval->payment_request->cost_center_id);
                }]))
                    ->whereIn('role_id', $approvalFlowOrders)
                    ->orWhere(function ($query) use ($approvalFlowOrders, $usersId) {
                        $query->whereIn('role_id', $approvalFlowOrders);
                        $query->whereIn('id', $usersId);
                        $query->with((['role' => function ($query) {
                            $query->where('filter_cost_center', false);
                        }]));
                    })
                    ->where('status', 0)
                    ->whereIn('id', $usersId)
                    ->get(['id', 'email', 'phone']);

                $usersMail = $usersNotify->pluck('email');
                $paymentRequest = PaymentRequestClean::with(['provider', 'company', 'cost_center', 'chart_of_accounts', 'approval'])->withOutGlobalScopes()->where('id', $accountApproval->payment_request->id)->first();
                $dataSendMail = NotificationService::generateDataSendRedisPaymentRequest($paymentRequest, $usersMail, 'Conta pendente de aprovação', 'payment-request-to-approve', $accountApproval->order, $maxOrder);
                NotificationService::sendEmail($dataSendMail);
            }
        }
       */
    }

    public function updateOrder($objRequest)
    {
        if (is_array($objRequest)) {
            if (array_key_exists('id', $objRequest)) {
                if (array_key_exists('order', $objRequest)) {
                    if (!is_null($objRequest['order'])) {
                        $this->alterOrder = true;
                        $this->order = $objRequest['order'];
                    }
                }
                return $objRequest['id'];
            } else {
                return $objRequest;
            }
        } else {
            return $objRequest;
        }
    }

    public function returnApprovalOrders($approvalOrderUser, $requestInfo)
    {
        if (array_key_exists('role', $requestInfo)) {
            $parFinalDeRolesEOrders = [];
            if (array_key_exists('role', $requestInfo)) {
                $approvalRoleRequest = $this->approvalFlow
                    ->where('role_id', $requestInfo['role'])
                    ->get(['order', 'group_approval_flow_id']);

                foreach ($approvalRoleRequest as $adr) {
                    foreach ($approvalOrderUser as $adu) {
                        if ($adr['order'] == $adu['order'] && $adr['group_approval_flow_id'] == $adu['group_approval_flow_id']) {
                            $parFinalDeRolesEOrders[] = $adr; // Adicionar a lista final
                            break;
                        }
                    }
                }
            }
            return $parFinalDeRolesEOrders;
        } else {
            return $approvalOrderUser->toArray();
        }
    }

    private function createAnalysisLog($requestInfo, $realApproval)
    {
        ApprovalLog::create([
            'user_id' => auth()->user()->id,
            'request' => json_encode($requestInfo),
            'real_approval' => json_encode($realApproval),
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\SupplyApprovalFlow;
use App\Models\ApprovalFlowSupply;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHasCostCenters;
use App\Models\TransferOrder;
use App\Models\User;
use App\Models\UserHasCostCenter;
use Illuminate\Http\Request;
use Config;

class ApprovalFlowSupplyByUserService
{
    private $supplyApprovalFlow;
    private $approvalFlow;
    private $purchaseOrder;
    private $user;

    public function __construct(SupplyApprovalFlow $supplyApprovalFlow, ApprovalFlowSupply $approvalFlow, PurchaseOrder $purchaseOrder, User $user)
    {
        $this->supplyApprovalFlow = $supplyApprovalFlow;
        $this->approvalFlow = $approvalFlow;
        $this->purchaseOrder = $purchaseOrder;
        $this->user = $user;
    }

    public function getAllAccountsForApproval($requestInfo)
    {
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $supplyApprovalFlow = Utils::search($this->supplyApprovalFlow, $requestInfo, ['order']);

        $supplyApprovalFlow = $this->supplyApprovalFlow->whereIn('order', $approvalFlowUserOrder->toArray())
            ->whereIn('status', [0, 2])
            ->whereRelation('purchase_order', 'deleted_at', '=', null)->get(['id_purchase_order'])->pluck('id_purchase_order')->toArray();

        $idUserApproval = [];

        if (auth()->user()->role->filter_cost_center_supply) {
            foreach ($supplyApprovalFlow as $purchaseOrderApproval) {
                $costCenters = PurchaseOrderHasCostCenters::where('purchase_order_id', $purchaseOrderApproval)->get();
                $costCenterId = [];
                $maxPercentage = 0;
                $constCenterEqual = false;
                foreach ($costCenters as $costCenter) {
                    if ($costCenter->percentage > $maxPercentage) {
                        $constCenterEqual = false;
                        unset($costCenterId);
                        $costCenterId = [$costCenter->cost_center_id];
                        $maxPercentage = $costCenter->percentage;
                    } else if ($costCenter->percentage == $maxPercentage) {
                        $constCenterEqual = true;
                        $maxPercentage = $costCenter->percentage;
                    }
                    if ($costCenter->percentage == $maxPercentage) {
                        if (!in_array($costCenter->cost_center_id, $costCenterId)) {
                            array_push($costCenterId, $costCenter->cost_center_id);
                        }
                    }
                }

                if ($constCenterEqual) {
                    $userApprovalByName = User::whereHas('cost_center', function ($query) use ($costCenterId) {
                        $query->whereIn('cost_center_id', $costCenterId);
                    });
                    if ($userApprovalByName->exists()) {
                        $userApprovalByName = $userApprovalByName->orderBy('name', 'asc')->first();
                        if ($userApprovalByName->id == auth()->user()->id) {
                            $idUserApproval[] = $purchaseOrderApproval;
                        }
                    }
                } else {
                    $userApprovalByName = User::whereHas('cost_center', function ($query) use ($costCenterId) {
                        $query->whereIn('cost_center_id', $costCenterId);
                    });
                    if ($userApprovalByName->where('id', auth()->user()->id)->exists()) {
                        $idUserApproval[] = $purchaseOrderApproval;
                    }
                }
            }
        }

        $supplyApprovalFlow = Utils::search($this->supplyApprovalFlow, $requestInfo, ['order']);

        $supplyApprovalFlow->whereIn('order', $approvalFlowUserOrder->toArray())
            ->whereIn('status', [0, 2])
            ->whereRelation('purchase_order', 'deleted_at', '=', null)
            ->with(['purchase_order', 'purchase_order.installments', 'approval_flow']);

        //filter cost center
        if (auth()->user()->role->filter_cost_center_supply) {
            $supplyApprovalFlow->whereIn('id_purchase_order', $idUserApproval);
        }

        $resultSupplyApprovalFlow = $supplyApprovalFlow->get(['id_purchase_order'])->pluck('id_purchase_order')->toArray();

        $add = [];
        $remove = [];

        if (TransferOrder::whereIn('flag', [1, 2])->exists()) {
            //check in transfer order tabel
            $transfers = TransferOrder::whereIn('flag', [1, 2])->get();

            foreach ($transfers as $trans) {
                $userExist = in_array(auth()->user()->id, $trans->users_ids);

                if ($userExist == false) {
                    $remove[] = $trans->purchase_order_id;
                } else {
                    $userIndex = array_search(auth()->user()->id, $trans->users_ids);
                    $count = $trans->approve_count;
                    if ($userIndex == $count) {
                        $add[] = $trans->purchase_order_id;
                    } else {
                        $remove[] = $trans->purchase_order_id;
                    }
                }
            }
            if (auth()->user()->role->filter_cost_center_supply) {
                foreach ($remove as $rem) {
                    foreach (array_keys($resultSupplyApprovalFlow, $rem) as $key) {
                        unset($resultSupplyApprovalFlow[$key]);
                    }
                }
            }
        }

        $ids = array_merge($resultSupplyApprovalFlow, $add);

        $getSupplyApprovalFlow = SupplyApprovalFlow::withoutGlobalScopes()->whereIn('id_purchase_order', $ids)
            ->with(['purchase_order', 'purchase_order.installments', 'approval_flow']);

        $getSupplyApprovalFlow->whereHas('purchase_order', function ($query) use ($requestInfo) {
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

        return Utils::pagination($getSupplyApprovalFlow, $requestInfo);
    }

    public function approveAccount($id)
    {
        $accountApproval = $this->supplyApprovalFlow->with('purchase_order')->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = 0;
        $approveCount = 0;
        $initOrder = $accountApproval->order;

        if (TransferOrder::where(['purchase_order_id' => $accountApproval->purchase_order->id/*, 'order' => $accountApproval->order , 'flag' => 1 */])->exists()) {
            if ($accountApproval->order == 0) {
                $accountApproval->order += 1;
                $approveCount =  0;
            } else {
                $transfer = TransferOrder::where(['purchase_order_id' => $accountApproval->purchase_order->id, 'order' => $accountApproval->order])->firstOrFail();
                if (($transfer->approve_count + 1) >= count($transfer->users_ids)) {
                    $approveCount = $transfer->approve_count + 1;
                    $this->updateTransferOrder($accountApproval->purchase_order->id, $accountApproval->order, $approveCount);
                    if ($accountApproval->order >= $maxOrder) {
                        $accountApproval->status = Config::get('constants.status.approved');
                        $accountApproval->order += 1;
                        $approveCount =  0;
                    } else {
                        $accountApproval->order += 1;
                        $approveCount =  0;
                    }
                } else {
                    $approveCount = $transfer->approve_count + 1;
                }
            }
            $this->updateTransferOrder($accountApproval->purchase_order->id, $accountApproval->order, $approveCount);
        } else {
            if ($accountApproval->order >= $maxOrder) {
                $accountApproval->status = Config::get('constants.status.approved');
                $accountApproval->order += 1;
            } else {
                $accountApproval->order += 1;
            }
        }

        $accountApproval->purchase_order->update([
            'approved_total_value' => $accountApproval->purchase_order->negotiated_total_value,
            'approved_installment_value' => $accountApproval->purchase_order->installments_total_value
        ]);

        $accountApproval->reason = null;
        $accountApproval->updated_at = now();
        $accountApproval->save();

        //if ($initOrder > $accountApproval->order) {
        $this->notifyUsers($accountApproval, $maxOrder, auth()->user());
        //}

        return response()->json([
            'Sucesso' => 'Pedido aprovado',
        ], 200);
    }

    public function approveManyAccounts($requestInfo)
    {
        if (array_key_exists('ids', $requestInfo)) {
            if (array_key_exists('reprove', $requestInfo) && $requestInfo['reprove'] == true) {
                foreach ($requestInfo['ids'] as $value) {
                    $accountApproval = $this->supplyApprovalFlow->findOrFail($value);
                    $maxOrder = $this->approvalFlow->max('order');
                    $accountApproval->status = Config::get('constants.status.disapproved');
                    $approveCount = 0;

                    if ($this->approvalFlow
                        ->where('order', $accountApproval->order)
                        ->where('role_id', auth()->user()->role_id)
                        ->doesntExist()
                    ) {
                        return response()->json([
                            'error' => 'Não é permitido a esse usuário reprovar ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
                        ], 422);
                    }

                    if (TransferOrder::where(['purchase_order_id' => $accountApproval->purchase_order->id, 'order' => $accountApproval->order/* , 'flag' => 1 */])->exists()) {

                        if ($accountApproval->order > $maxOrder) {
                            $accountApproval->order = Config::get('constants.status.open');
                            $approveCount = 0;
                        } else if ($accountApproval->order != 0) {
                            $accountApproval->order -= 1;
                            $approveCount = 0;
                        }

                        //if ($accountApproval->order == 0) {
                        //    TransferOrder::where('purchase_order_id', $accountApproval->purchase_order->id)->delete();
                        //} else {
                        $this->updateTransferOrder($accountApproval->purchase_order->id, $accountApproval->order, $approveCount);
                        //}
                    } else {
                        if ($accountApproval->order > $maxOrder) {
                            $accountApproval->order = Config::get('constants.status.open');
                        } else if ($accountApproval->order != 0) {
                            $accountApproval->order -= 1;
                        }
                    }

                    $accountApproval->reason = null;
                    //$accountApproval->reason_to_reject_id = null;
                    $accountApproval->fill($requestInfo)->save();

                    $this->notifyUsers($accountApproval, $maxOrder, auth()->user());
                }
                return response()->json([
                    'Sucesso' => 'Contas reprovadas',
                ], 200);
            } else {
                foreach ($requestInfo['ids'] as $value) {
                    $accountApproval = $this->supplyApprovalFlow->with('purchase_order')->findOrFail($value);
                    $maxOrder = $this->approvalFlow->max('order');
                    $accountApproval->status = 0;
                    $approveCount = 0;
                    $initOrder = $accountApproval->order;

                    if ($this->approvalFlow
                        ->where('order', $accountApproval->order)
                        ->where('role_id', auth()->user()->role_id)
                        ->doesntExist()
                    ) {
                        return response()->json([
                            'error' => 'Não é permitido a esse usuário aprovar ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
                        ], 422);
                    }

                    if (TransferOrder::where(['purchase_order_id' => $accountApproval->purchase_order->id/*, 'order' => $accountApproval->order , 'flag' => 1 */])->exists()) {
                        if ($accountApproval->order == 0) {
                            $accountApproval->order += 1;
                            $approveCount =  0;
                        } else {
                            $transfer = TransferOrder::where(['purchase_order_id' => $accountApproval->purchase_order->id, 'order' => $accountApproval->order/* , 'flag' => 1 */])->firstOrFail();
                            if (($transfer->approve_count + 1) >= count($transfer->users_ids)) {
                                $approveCount = $transfer->approve_count + 1;
                                $this->updateTransferOrder($accountApproval->purchase_order->id, $accountApproval->order, $approveCount);
                                if ($accountApproval->order >= $maxOrder) {
                                    $accountApproval->status = Config::get('constants.status.approved');
                                    $accountApproval->order += 1;
                                    $approveCount =  0;
                                } else {
                                    $accountApproval->order += 1;
                                    $approveCount =  0;
                                }
                            } else {
                                $approveCount = $transfer->approve_count + 1;
                            }
                        }
                        $this->updateTransferOrder($accountApproval->purchase_order->id, $accountApproval->order, $approveCount);
                    } else {
                        if ($accountApproval->order >= $maxOrder) {
                            $accountApproval->status = Config::get('constants.status.approved');
                        } else {
                            $accountApproval->order += 1;
                        }
                    }

                    $accountApproval->purchase_order->update([
                        'approved_total_value' => $accountApproval->purchase_order->negotiated_total_value,
                        'approved_installment_value' => $accountApproval->purchase_order->installments_total_value
                    ]);

                    $accountApproval->reason = null;
                    //$accountApproval->reason_to_reject_id = null;
                    $accountApproval->updated_at = now();
                    $accountApproval->save();

                    //if ($initOrder > $accountApproval->order) {
                    $this->notifyUsers($accountApproval, $maxOrder, auth()->user());
                    //}
                }
                return response()->json([
                    'Sucesso' => 'Pedido aprovado',
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
        $accountApproval = $this->supplyApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = Config::get('constants.status.disapproved');
        $approveCount = 0;

        if (TransferOrder::where(['purchase_order_id' => $accountApproval->purchase_order->id, 'order' => $accountApproval->order])->exists()) {

            if ($accountApproval->order > $maxOrder) {
                $accountApproval->order = 0;
                $approveCount =  0;
            } else if ($accountApproval->order == 0) {
                $accountApproval->reason = $request->reason;
                $approveCount =  0;
            } else {
                $accountApproval->order -= 1;
                $approveCount =  0;
            }

            //if ($accountApproval->order == 0) {
            //    TransferOrder::where('purchase_order_id', $accountApproval->purchase_order->id)->delete();
            //} else {
            $this->updateTransferOrder($accountApproval->purchase_order->id, $accountApproval->order, $approveCount);
            //}
        } else {
            if ($accountApproval->order > $maxOrder) {
                $accountApproval->order = 0;
            } else if ($accountApproval->order == 0) {
                $accountApproval->reason = $request->reason;
            } else {
                $accountApproval->order -= 1;
            }
        }

        $accountApproval->reason = $request->reason;
        $accountApproval->save();

        $this->notifyUsers($accountApproval, $maxOrder, auth()->user());

        return response()->json([
            'Sucesso' => 'Conta reprovada',
        ], 200);
    }

    public function cancelAccount($id, Request $request)
    {
        $accountApproval = $this->supplyApprovalFlow->findOrFail($id);
        $accountApproval->status = Config::get('constants.status.canceled');
        $accountApproval->reason = $request->reason;

        if (TransferOrder::where(['purchase_order_id' => $accountApproval->purchase_order->id])->exists()) {
            TransferOrder::where('purchase_order_id', $accountApproval->purchase_order->id)->delete();
        }
        return $accountApproval->save();
    }

    public function notifyUsers($accountApproval, $maxOrder, $approveUser)
    {
        if (($accountApproval->order - 1) >= $maxOrder) {
            //Gestor da Área, Gestor de Suprimentos e Equipe de Suprimentos
            $usersMail = [];
            NotificationService::generateDataSendRedisPurchaseOrder($accountApproval->purchase_order, $usersMail, 'Pedido de Compra totalmente aprovada', 'purchase-order-fully-approved', $approveUser, '', '');
        } else {
            if (TransferOrder::where(['purchase_order_id' => $accountApproval->purchase_order->id])->exists()) {
                //check transfer table
                $transfer = TransferOrder::where(['purchase_order_id' => $accountApproval->purchase_order->id, 'order' => $accountApproval->order, 'flag' => 1])->firstOrFail(['users_ids', 'approve_count']);

                $usersNotify = $this->user->where('id', $transfer->users_ids[$transfer->approve_count])
                    ->where('deleted_at', null)
                    ->where('status', 0)
                    ->get(['id', 'email', 'phone']);

                $usersMail = $usersNotify->pluck('email');
            } else {
                //responsável pela aprovação
                $approvalFlowOrders = $this->approvalFlow
                    ->where('order', $accountApproval->order)
                    ->get('role_id')->pluck('role_id');

                //filtrar por centro de custo e com maior peso
                $costCenterId = [];
                $maxPercentage = 0;
                $constCenterEqual = false;

                foreach ($accountApproval->purchase_order->cost_centers as $costCenter) {
                    if ($costCenter->percentage > $maxPercentage) {
                        $constCenterEqual = false;
                        unset($costCenterId);
                        $costCenterId = [$costCenter->cost_center_id];
                        $maxPercentage = $costCenter->percentage;
                    } else if ($costCenter->percentage == $maxPercentage) {
                        $constCenterEqual = true;
                        $maxPercentage = $costCenter->percentage;
                    }
                    if ($costCenter->percentage == $maxPercentage) {
                        if (!in_array($costCenter->cost_center_id, $costCenterId)) {
                            array_push($costCenterId, $costCenter->cost_center_id);
                        }
                    }
                }

                if ($constCenterEqual) {
                    $usersNotify = $this->user->whereIn('role_id', $approvalFlowOrders)
                        ->where('deleted_at', null)
                        ->where('role_id', '!=', 1)
                        ->whereHas('cost_center', function ($query) use ($costCenterId) {
                            $query->where('cost_center_id', $costCenterId[0]);
                        })
                        ->where('status', 0)
                        ->get(['id', 'email', 'phone']);
                } else {
                    $usersNotify = $this->user->whereIn('role_id', $approvalFlowOrders)
                        ->where('deleted_at', null)
                        ->where('role_id', '!=', 1)
                        ->whereHas('cost_center', function ($query) use ($costCenterId) {
                            $query->whereIn('cost_center_id', $costCenterId);
                        })
                        ->where('status', 0)
                        ->get(['id', 'email', 'phone']);
                }

                $usersMail = $usersNotify->pluck('email');
            }

            NotificationService::generateDataSendRedisPurchaseOrder($accountApproval->purchase_order, $usersMail->toArray(), 'Pedido de Compra pendente de aprovação', 'purchase-order-to-approve', $approveUser, '', 'aprovado');
        }
    }

    public function updateTransferOrder($purchaseOrderId, $order, $count)
    {
        TransferOrder::where([
            'purchase_order_id' => $purchaseOrderId
        ])
            ->update([
                'flag' => 0
            ]);

        TransferOrder::where([
            'purchase_order_id' => $purchaseOrderId,
            'order' => $order
        ])
            ->update([
                'flag' => 1,
                'approve_count' => $count
            ]);
    }
}

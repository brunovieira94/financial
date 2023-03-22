<?php

namespace App\Services;

use App\Models\ApprovalFlowSupply;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHasCostCenters;
use App\Models\SupplyApprovalFlow;
use App\Models\TransferOrder;
use App\Models\User;

class TransferOrderService
{
    private $transferOrder;
    private $with = [];

    public function __construct(TransferOrder $transferOrder)
    {
        $this->transferOrder = $transferOrder;
    }

    public function getUserApprover($id)
    {
        if (PurchaseOrder::where('id', $id)->exists()) {
            $orderFlag = SupplyApprovalFlow::where('id_purchase_order', $id)->firstOrFail();
            $response = [];
            if (TransferOrder::where('purchase_order_id', $id)->exists()) {
                $transfers = TransferOrder::where('purchase_order_id', $id)->get();
                foreach ($transfers as $tranfer) {
                    $userId = [];
                    foreach ($tranfer['users_ids'] as $transferUser) {
                        $userId[] = User::where('id', $transferUser)->get(['id', 'name', 'email']);
                    }

                    $users = [];
                    foreach ($userId as $item) {
                        $users[] = $item[0];
                    }

                    if ($orderFlag->status == 1) {
                        $flag = 2;
                    } else {
                        $flag = $tranfer->flag;
                    }

                    $response[] = [
                        'purchase_order_id' => $id,
                        'order' => $tranfer->order,
                        'flag' => $flag,
                        'users_ids' => $users
                    ];
                }
            } else {
                $approvalFlows = ApprovalFlowSupply::all();
                $costCenters = PurchaseOrderHasCostCenters::where('purchase_order_id', $id)->get();
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

                foreach ($approvalFlows as $approvalFlow) {
                    if ($approvalFlow->order != 0 && $approvalFlow->role_id != 1) {
                        $finalUsers = [];
                        $users = User::where('role_id', $approvalFlow->role_id)->with('role', 'cost_center')->get();
                        foreach ($users as $user) {
                            if ($user->role->filter_cost_center_supply == true) {
                                foreach ($user->cost_center as $center) {
                                    if (in_array($center->id, $costCenterId)) {
                                        $finalUsers[] = [
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'email' => $user->email
                                        ];
                                    }
                                }
                            } else {
                                $finalUsers[] = [
                                    'id' => $user->id,
                                    'name' => $user->name,
                                    'email' => $user->email
                                ];
                            }
                        }

                        if ($orderFlag->status == 1 && (count($approvalFlows->groupBy('order')) - 1) == $approvalFlow->order) {
                            $flag = 2;
                        } else {
                            $flag = ($approvalFlow->order == $orderFlag->order) ? 1 : 0;
                        }

                        $response[] = [
                            'purchase_order_id' => $id,
                            'order' => $approvalFlow->order,
                            'flag' => $flag,
                            'users_ids' => $finalUsers
                        ];
                    }
                }

                $occurences = array_count_values(array_column($response, 'order'));

                $to_unset = [];
                foreach ($occurences as $order => $count) {
                    if ($count == 1) continue;

                    $indexes = array_keys(array_column($response, 'order'), $order);

                    $first_index = $indexes[0];
                    unset($indexes[0]);

                    $model = $response[$first_index];
                    $model['users_ids'] = [$model['users_ids']];

                    foreach ($indexes as $index) {
                        array_push($model['users_ids'], $response[$index]['users_ids']);
                        array_push($to_unset, $index);
                    }

                    $users = [];
                    foreach ($model['users_ids'] as $item) {
                        foreach ($item as $i) {
                            $users[] = $i;
                        }
                    }

                    $response[$first_index] = [
                        'purchase_order_id' => $model['purchase_order_id'],
                        'order' => $model['order'],
                        'flag' => $model['flag'],
                        'users_ids' => $users
                    ];
                }

                $response = array_diff_key($response, array_flip($to_unset));

                $response = array_values($response);
            }
            return $response;
        } else {
            return response()->json([
                'error' => 'Pedido de Compra não existe.'
            ], 500);
        }
    }

    public function postUserApprover($requestInfo)
    {
        $approvalFlows = ApprovalFlowSupply::all();
        foreach ($requestInfo['transfer_order'] as $transfer) {
            $purchase_order_id = $transfer['purchase_order_id'];
            if (PurchaseOrder::where('id', $transfer['purchase_order_id'])->exists()) {
                if (TransferOrder::where([
                    'purchase_order_id' => $transfer['purchase_order_id'],
                    'order' => $transfer['order'],
                ])->exists()) {
                    if (($transfer['order'] == (count($approvalFlows->groupBy('order')) - 1)) && $transfer['flag'] == 2) {
                        SupplyApprovalFlow::where('id_purchase_order', $transfer['purchase_order_id'])->update([
                            'order' => $transfer['order'],
                            'status' => 0
                        ]);
                        $approve_count = 0;
                    } else {
                        if ($transfer['flag'] == 2) {
                            $this->transferOrder->where([
                                'purchase_order_id' => $transfer['purchase_order_id'],
                                'order' => $transfer['order'],
                            ])->update([
                                'flag' => 0
                            ]);
                        }
                    }
                    if (isset($approve_count)) {
                        $this->transferOrder->where([
                            'purchase_order_id' => $transfer['purchase_order_id'],
                            'order' => $transfer['order'],
                        ])->update([
                            'flag' => $transfer['flag'],
                            'users_ids' => $transfer['users_ids'],
                            'approve_count' => $approve_count
                        ]);
                    } else {
                        $this->transferOrder->where([
                            'purchase_order_id' => $transfer['purchase_order_id'],
                            'order' => $transfer['order'],
                        ])->update([
                            //'flag' => $transfer['flag'],
                            'users_ids' => $transfer['users_ids']
                        ]);
                    }
                } else {
                    if (($transfer['order'] == (count($approvalFlows->groupBy('order')) - 1)) && $transfer['flag'] == 2) {
                        SupplyApprovalFlow::where('id_purchase_order', $transfer['purchase_order_id'])->update([
                            'order' => $transfer['order'],
                            'status' => 0
                        ]);
                    }
                    $this->transferOrder->create([
                        'purchase_order_id' => $transfer['purchase_order_id'],
                        'order' => $transfer['order'],
                        'flag' => $transfer['flag'],
                        'users_ids' => $transfer['users_ids'],
                    ]);
                }
            } else {
                return response()->json([
                    'error' => 'O pedido de Compra não existe.',
                ], 422);
            }
        }

        //notificar user
        $this->notifyUsers($purchase_order_id, auth()->user());

        return response()->json([
            'message' => 'Transferencia do pedido de compra feito com sucesso.',
        ], 200);
    }

    public function cancelTransferOrder($id)
    {
        if (TransferOrder::where('purchase_order_id', $id)->exists()) {
            $this->transferOrder->where('purchase_order_id', $id)->delete();
            return response()->json([
                'message' => 'Transferencia do pedido de compra cancelado.',
            ], 200);
        } else {
            return response()->json([
                'error' => 'A transferencia deste pedido de compra não existe.',
            ], 422);
        }
    }

    public function notifyUsers($purchase_order_id, $approveUser)
    {
        $orderSupply = SupplyApprovalFlow::where('id_purchase_order', $purchase_order_id)->firstOrFail(['order', 'status']);
        $transfer = TransferOrder::where([
            'purchase_order_id' => $purchase_order_id,
            'order' => $orderSupply->order
        ])
            ->whereIn('flag', [1, 2])->firstOrFail();

        if (!empty($transfer->users_ids)) {
            $usersNotify = User::where('id', $transfer->users_ids[0])->get(['id', 'email', 'phone']);
            $usersMail = $usersNotify->pluck('email');

            $purchase_order = PurchaseOrder::where('id', $purchase_order_id)->firstOrFail();

            NotificationService::generateDataSendRedisPurchaseOrder($purchase_order, $usersMail->toArray(), 'Pedido de Compra pendente de aprovação', 'purchase-order-to-approve', $approveUser, '', 'transferido');
        }
    }
}

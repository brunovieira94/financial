<?php

namespace App\Services;

use App\Models\SupplyApprovalFlow;
use App\Models\ApprovalFlowSupply;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHasCostCenters;
use App\Models\User;
use App\Models\UserHasCostCenter;
use Illuminate\Http\Request;
use Config;

class ApprovalFlowSupplyByUserService
{
    private $supplyApprovalFlow;
    private $approvalFlow;

    public function __construct(SupplyApprovalFlow $supplyApprovalFlow, ApprovalFlowSupply $approvalFlow, PurchaseOrder $purchaseOrder)
    {
        $this->supplyApprovalFlow = $supplyApprovalFlow;
        $this->approvalFlow = $approvalFlow;
        $this->purchaseOrder = $purchaseOrder;
    }

    public function getAllAccountsForApproval($requestInfo)
    {
        $approvalFlowUserOrder = $this->approvalFlow->where('role_id', auth()->user()->role_id)->get(['order']);

        if (!$approvalFlowUserOrder)
            return response([], 404);

        $supplyApprovalFlow = $this->supplyApprovalFlow->whereIn('order', $approvalFlowUserOrder->toArray())
            ->where('status', 0)
            ->whereRelation('purchase_order', 'deleted_at', '=', null)
            ->with(['purchase_order', 'purchase_order.installments', 'approval_flow'])->get();


        $idUserApproval = [];

        if (auth()->user()->role->filter_cost_center_supply) {
            foreach ($supplyApprovalFlow as $purchaseOrderApproval) {
                $costCenters = PurchaseOrderHasCostCenters::where('purchase_order_id', $purchaseOrderApproval->id_purchase_order)->get();
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
                            $idUserApproval[] = $purchaseOrderApproval->id;
                        }
                    }
                } else {
                    $userApprovalByName = User::whereHas('cost_center', function ($query) use ($costCenterId) {
                        $query->whereIn('cost_center_id', $costCenterId);
                    });
                    if ($userApprovalByName->where('id', auth()->user()->id)->exists()) {
                        $idUserApproval[] = $purchaseOrderApproval->id;
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
            $supplyApprovalFlow->whereIn('id', $idUserApproval);
        }

        $supplyApprovalFlow->whereHas('purchase_order', function ($query) use ($requestInfo) {
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

        return Utils::pagination($supplyApprovalFlow, $requestInfo);
    }

    public function approveAccount($id)
    {
        $accountApproval = $this->supplyApprovalFlow->with('purchase_order')->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = 0;

        if ($accountApproval->order >= $maxOrder) {
            $accountApproval->status = Config::get('constants.status.approved');
            $accountApproval->order += 1;
        } else {
            $accountApproval->order += 1;
        }

        $accountApproval->reason = null;
        $accountApproval->save();
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

                    if ($this->approvalFlow
                        ->where('order', $accountApproval->order)
                        ->where('role_id', auth()->user()->role_id)
                        ->doesntExist()
                    ) {
                        return response()->json([
                            'erro' => 'Não é permitido a esse usuário reprovar ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
                        ], 422);
                    }

                    if ($accountApproval->order > $maxOrder) {
                        $accountApproval->order = Config::get('constants.status.open');
                    } else if ($accountApproval->order != 0) {
                        $accountApproval->order -= 1;
                    }

                    $accountApproval->reason = null;
                    //$accountApproval->reason_to_reject_id = null;
                    $accountApproval->fill($requestInfo)->save();
                }
                return response()->json([
                    'Sucesso' => 'Contas reprovadas',
                ], 200);
            } else {
                foreach ($requestInfo['ids'] as $value) {
                    $accountApproval = $this->supplyApprovalFlow->with('purchase_order')->findOrFail($value);
                    $maxOrder = $this->approvalFlow->max('order');
                    $accountApproval->status = 0;

                    if ($this->approvalFlow
                        ->where('order', $accountApproval->order)
                        ->where('role_id', auth()->user()->role_id)
                        ->doesntExist()
                    ) {
                        return response()->json([
                            'erro' => 'Não é permitido a esse usuário aprovar ' . $accountApproval->payment_request_id . ', modifique o fluxo de aprovação.',
                        ], 422);
                    }

                    if ($accountApproval->order >= $maxOrder) {
                        $accountApproval->status = Config::get('constants.status.approved');
                    } else {
                        $accountApproval->order += 1;
                    }

                    $accountApproval->reason = null;
                    //$accountApproval->reason_to_reject_id = null;
                    $accountApproval->save();
                }
                return response()->json([
                    'Sucesso' => 'Pedido aprovado',
                ], 200);
            }
        } else {
            return response()->json([
                'erro' => 'Nenhuma conta selecionada',
            ], 422);
        }
    }

    public function reproveAccount($id, Request $request)
    {
        $accountApproval = $this->supplyApprovalFlow->findOrFail($id);
        $maxOrder = $this->approvalFlow->max('order');
        $accountApproval->status = Config::get('constants.status.disapproved');

        if ($accountApproval->order > $maxOrder) {
            $accountApproval->order = 0;
        } else if ($accountApproval->order == 0) {
            $accountApproval->reason = $request->reason;
        } else {
            $accountApproval->order -= 1;
        }
        $accountApproval->reason = $request->reason;
        return $accountApproval->save();
    }

    public function cancelAccount($id, Request $request)
    {
        $accountApproval = $this->supplyApprovalFlow->findOrFail($id);
        $accountApproval->status = Config::get('constants.status.canceled');
        $accountApproval->reason = $request->reason;
        return $accountApproval->save();
    }
}

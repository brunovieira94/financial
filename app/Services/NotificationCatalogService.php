<?php

namespace App\Services;

use App\Models\NotificationCatalog;
use App\Models\NotificationCatalogHasRoles;
use App\Models\NotificationCatalogHasUsers;
use App\Models\PurchaseOrderHasCostCenters;
use App\Models\SupplyApprovalFlow;
use Carbon\Carbon;

class NotificationCatalogService
{
    private $notificationCatalog;
    private $notificationCatalogHasRoles;
    private $notificationCatalogHasUsers;

    private $with = ['roles', 'users'];

    public function __construct(NotificationCatalog $notificationCatalog, NotificationCatalogHasRoles $notificationCatalogHasRoles, NotificationCatalogHasUsers $notificationCatalogHasUsers)
    {
        $this->notificationCatalog = $notificationCatalog;
        $this->notificationCatalogHasRoles = $notificationCatalogHasRoles;
        $this->notificationCatalogHasUsers = $notificationCatalogHasUsers;
    }

    public function getAllNotificationCatalog($requestInfo)
    {
        $notificationCatalogs = Utils::search($this->notificationCatalog, $requestInfo);
        return Utils::pagination($notificationCatalogs->with($this->with), $requestInfo);
    }

    public function getNotificationCatalogById($id)
    {
        return $this->notificationCatalog->findOrFail($id)->where('id', $id)->with($this->with)->get();
    }

    public function putNotificationCatalog($id, $requestInfo)
    {
        if (array_key_exists('users', $requestInfo)) {
            foreach ($requestInfo['users'] as $userId) {
                if ($this->notificationCatalogHasUsers->where([
                    'notification_catalog_id' => $id,
                    'user_id' => $userId
                ])->doesntExist()) {
                    $this->notificationCatalogHasUsers->create([
                        'notification_catalog_id' => $id,
                        'user_id' => $userId
                    ]);
                }
            }
            $collectionHasUsers = $this->notificationCatalogHasUsers->where('notification_catalog_id', $id)->whereNotIn('user_id', $requestInfo['users'])->get(['id']);
            $this->notificationCatalogHasUsers->destroy($collectionHasUsers->toArray());
        }

        if (array_key_exists('roles', $requestInfo)) {
            foreach ($requestInfo['roles'] as $roleId) {
                if ($this->notificationCatalogHasRoles->where([
                    'notification_catalog_id' => $id,
                    'role_id' => $roleId
                ])->doesntExist()) {
                    $this->notificationCatalogHasRoles->create([
                        'notification_catalog_id' => $id,
                        'role_id' => $roleId
                    ]);
                }
            }
            $collectionHasRoles = $this->notificationCatalogHasRoles->where('notification_catalog_id', $id)->whereNotIn('role_id', $requestInfo['roles'])->get(['id']);
            $this->notificationCatalogHasRoles->destroy($collectionHasRoles->toArray());
        }

        return $this->notificationCatalog->with($this->with)->findOrFail($id);
    }

    public function putNotificationCatalogStatus($requestInfo)
    {
        if (array_key_exists('to_activate', $requestInfo)) {
            foreach ($requestInfo['to_activate'] as $activeid) {
                $this->notificationCatalog->findOrFail($activeid)->fill([
                    'active' => 1
                ])->save();
            }
        }

        if (array_key_exists('to_disable', $requestInfo)) {
            foreach ($requestInfo['to_disable'] as $disableId) {
                $this->notificationCatalog->where('id', $disableId)->update([
                    'active' => 0
                ]);
            }
        }

        return true;
    }

    public static function getTeste()
    {
        if (NotificationCatalog::where(['type' => 'purchase-order-renovation', 'active' => true, 'schedule' => 1])->exists()) {
            $purchaseOrdersIds = [];
            $notificationId = NotificationCatalog::where(['type' => 'purchase-order-renovation', 'active' => true, 'schedule' => 1])->firstOrFail(['id', 'type']);

            $purchaseOrders = SupplyApprovalFlow::join('purchase_order_has_services', 'purchase_order_has_services.purchase_order_id', 'supply_approval_flows.id_purchase_order')
                ->join('purchase_orders', 'purchase_orders.id', 'supply_approval_flows.id_purchase_order')
                ->join('providers', 'providers.id', 'purchase_orders.provider_id')
                ->select('supply_approval_flows.id_purchase_order', 'purchase_order_has_services.initial_date', 'purchase_order_has_services.contract_time', 'purchase_order_has_services.contract_frequency', 'purchase_order_has_services.notice_time_to_renew', 'purchase_orders.provider_id', 'providers.trade_name', 'providers.company_name')
                ->where('supply_approval_flows.status', 1)
                ->whereDate('purchase_order_has_services.end_date', '>=', Carbon::today())
                ->get();

            foreach ($purchaseOrders as $purchaseOrder) {
                if ($purchaseOrder->contract_frequency == 0) {
                    $endDate = Carbon::parse($purchaseOrder->initial_date)->addDays($purchaseOrder->contract_time);
                } else {
                    $endDate = Carbon::parse($purchaseOrder->initial_date)->addMonths($purchaseOrder->contract_time);
                }

                if ($endDate->subDays($purchaseOrder->notice_time_to_renew) == Carbon::today()) {
                    $purchaseOrdersIds[] = [
                        'purchase_order_id' => $purchaseOrder->id_purchase_order,
                        'days_to_end' => $purchaseOrder->notice_time_to_renew,
                        'end_date' => $endDate->addDays($purchaseOrder->notice_time_to_renew)->format('d-m-Y'),
                        'provider_trade_name' => $purchaseOrder->trade_name,
                        'provider_company_name' => $purchaseOrder->company_name
                    ];
                }
            }

            $mails = [];
            $filterMails = [];
            $users = NotificationCatalogHasUsers::where('notification_catalog_id', $notificationId->id)->with('user')->get();
            foreach ($users as $user) {
                if (!in_array($user->user->email, $mails, true)) {
                    array_push($mails, $user->user->email);
                }
            }

            $roles = NotificationCatalogHasRoles::where('notification_catalog_id', $notificationId->id)->with('user', 'role')->get();

            foreach ($roles as $role) {
                if ($role->role->filter_cost_center_supply == 0) {
                    foreach ($role->user as $roleUser) {
                        if (!in_array($roleUser->email, $mails, true)) {
                            array_push($mails, $roleUser->email);
                        }
                    }
                } else {
                    foreach ($role->user as $roleUserFilter) {
                        if (!in_array($roleUserFilter->email, $filterMails, true)) {
                            array_push($filterMails, $roleUserFilter->email);
                        }
                        foreach ($roleUserFilter->cost_center as $teste2) {
                            $filterPurchaseOrdersIds = [];
                            foreach ($purchaseOrdersIds as $purchaseOrdersIdFilter) {
                                foreach (PurchaseOrderHasCostCenters::where('purchase_order_id', $purchaseOrdersIdFilter['purchase_order_id'])->get() as $teste) {
                                    if ($teste->cost_center_id == $teste2->id) {
                                        if (!in_array($purchaseOrdersIdFilter, $filterPurchaseOrdersIds, true)) {
                                            array_push($filterPurchaseOrdersIds, $purchaseOrdersIdFilter);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($filterPurchaseOrdersIds) {

                        //NotificationService::dailyPurchaseOrderRenewal($filterPurchaseOrdersIds, $filterMails, $notificationId->type);
                        dump($filterMails, $filterPurchaseOrdersIds);
                    }
                }
            }
            //NotificationService::dailyPurchaseOrderRenewal($purchaseOrdersIds, $mails, $notificationId->type);
            dump($purchaseOrdersIds, $mails, $notificationId->type);

            dd(auth()->user()->name, auth()->user()->id/* , auth()->user()->cost_center */);
        }
    }
}

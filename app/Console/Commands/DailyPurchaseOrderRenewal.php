<?php

namespace App\Console\Commands;

use App\Models\NotificationCatalog;
use App\Models\NotificationCatalogHasRoles;
use App\Models\NotificationCatalogHasUsers;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHasCostCenters;
use App\Models\SupplyApprovalFlow;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DailyPurchaseOrderRenewal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:purchase-order-renewal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send emails daily with purchase orders to be renewed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
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
                        NotificationService::dailyPurchaseOrderRenewal($filterPurchaseOrdersIds, $filterMails, $notificationId->type);
                    }
                }
            }
            NotificationService::dailyPurchaseOrderRenewal($purchaseOrdersIds, $mails, $notificationId->type);
        }
    }
}

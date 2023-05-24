<?php

namespace App\Services;

use App\Http\Resources\reports\RouteAccountsApprovalFlowLog;
use App\Http\Resources\reports\RouteBillingLog;
use App\Http\Resources\reports\RouteHotelLog;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowLog;
use App\Models\BillingLog;
use App\Models\HotelLog;
use App\Models\ApprovalFlow;
use App\Models\HotelApprovalFlow;
use App\Models\LogActivity;
use App\Models\SupplyApprovalFlow;

class LogsService
{

    public function getAllLogs($requestInfo)
    {
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return LogActivity::orderBy($orderBy, $order)->paginate($perPage);;
    }

    public function getLogs($log_name, $subject_id, $requestInfo)
    {
        return LogActivity::where([
            ['log_name', $log_name],
            ['subject_id', $subject_id]
        ])->get();
    }

    public function getPaymentRequestLogs($id, $requestInfo)
    {
        if (AccountsPayableApprovalFlow::where('payment_request_id', $id)->exists()) {
            $approvalFlow = AccountsPayableApprovalFlow::where('payment_request_id', $id)->first();
            $logPaymentRequest =  LogActivity::where([
                ['log_name', 'payment_request'],
                ['subject_id', $id]
            ])->orWhere(function ($q) use ($approvalFlow) {
                return $q->where('log_name', 'accounts_payable_approval_flows')->where('subject_id', $approvalFlow->id);
            })->orderBy('created_at', 'asc')->get();
        } else {
            $logPaymentRequest =  LogActivity::where([
                ['log_name', 'payment_request'],
                ['subject_id', $id]
            ])->orderBy('created_at', 'asc')->get();
        }

        $retorno = [];

        foreach ($logPaymentRequest as $log) {
            if ($log['log_name'] == 'accounts_payable_approval_flows') {
                $status = '';

                switch ($log['properties']['attributes']['status']) {

                    case 0:
                        $status = 'approved';
                        break;
                    case 2:
                        $status = 'rejected';
                        break;
                    case 3:
                        $status = 'canceled';
                        break;
                    case 1:
                        $status = 'approved';
                        break;
                    case 8:
                        $status = 'multiple-approval';
                        break;
                    case 9:
                        $status = 'transfer-approval';
                        break;
                    default:
                        $status = 'default';
                }

                $reason = null;
                $concatenate = false;

                if ($log['properties']['attributes']['reason_to_reject_id'] != null) {
                    $reason = $log['properties']['attributes']['reason_to_reject']['title'];
                    $concatenate = true;
                }
                if ($log['properties']['attributes']['reason'] != null) {
                    if ($concatenate) {
                        $reason = $reason . ' - ' . $log['properties']['attributes']['reason'];
                    } else {
                        $reason = $log['properties']['attributes']['reason'];
                    }
                }

                $retorno[] = [
                    'type' => $status,
                    'createdAt' => $log['created_at'] ?? '',
                    'description' => $log['description'] ?? '',
                    'causerUser' => $log['causer_object']['name'] ?? '',
                    'causerUserRole' => $log['causer_object']['role']['title'] ?? '',
                    'createdUser' => $log['properties']['attributes']['payment_request']['user']['name'] ?? '',
                    'motive' => $reason,
                    'stage' => isset($log['properties']['old']['order']) ? $log['properties']['old']['order'] + 1 : '', //front exibe a etapa com adição de 1
                ];
            } else if ($log['log_name'] == 'payment_request') {
                $retorno[] = [
                    'type' => $log['description'],
                    'createdAt' => $log['created_at'],
                    'description' => $log['description'],
                    'causerUser' => $log['causer_object']['name'],
                    'causerUserRole' => $log['causer_object']['role']['title'],
                    'createdUser' => $log['properties']['attributes']['user']['name'],
                ];
            }
        }
        return $retorno;
    }

    public function getPurchaseOrderLogs($id, $requestInfo)
    {
        if (SupplyApprovalFlow::where('id_purchase_order', $id)->exists()) {
            $approvalFlow = SupplyApprovalFlow::where('id_purchase_order', $id)->first();
            $logPurchaseOrder =  LogActivity::where([
                ['log_name', 'purchase_orders'],
                ['subject_id', $id]
            ])->orWhere(function ($q) use ($approvalFlow) {
                return $q->where('log_name', 'supply_approval_flows')->where('subject_id', $approvalFlow->id);
            })->orderBy('created_at', 'asc')->get();
        } else {
            $logPurchaseOrder =  LogActivity::where([
                ['log_name', 'purchase_orders'],
                ['subject_id', $id]
            ])->orderBy('created_at', 'asc')->get();
        }

        $retorno = [];

        foreach ($logPurchaseOrder as $log) {
            if ($log['log_name'] == 'supply_approval_flows') {
                $status = '';
                switch ($log['properties']['attributes']['status']) {
                    case 0:
                        $status = 'approved';
                        break;
                    case 2:
                        $status = 'rejected';
                        break;
                    case 3:
                        $status = 'canceled';
                        break;
                    case 1:
                        $status = 'approved';
                        break;
                    default:
                        $status = 'default';
                }

                $retorno[] = [
                    'type' => $status,
                    //'order' => $log['properties']['attributes']['order'],
                    'createdAt' => $log['created_at'],
                    'description' => $log['description'],
                    'causerUser' => $log['causer_object']['name'],
                    'causerUserRole' => isset($log['causer_object']['role']) ? $log['causer_object']['role']['title'] : '',
                    'createdUser' => $log['properties']['attributes']['purchase_order']['user']['name'] ?? '',
                    'motive' => $log['properties']['attributes']['reason']
                ];
            } else if ($log['log_name'] == 'purchase_orders') {
                $retorno[] = [
                    'type' => $log['description'],
                    'createdAt' => $log['created_at'],
                    'description' => $log['description'],
                    'causerUser' => $log['causer_object']['name'],
                    'causerUserRole' => isset($log['causer_object']['role']) ? $log['causer_object']['role']['title'] : '',
                    'createdUser' => isset($log['properties']['attributes']['user']) ? $log['properties']['attributes']['user']['name'] : '',
                ];
            }
        }
        return $retorno;
    }

    // public function getBillingLogs($id, $requestInfo)
    // {

    //     $logBilling =  LogActivity::where([
    //         ['log_name', 'billing'],
    //         ['subject_id', $id]
    //     ])->orderBy('created_at', 'asc')->get();
    //     $retorno = [];

    //     foreach ($logBilling as $log) {
    //         $status = '';
    //         switch ($log['properties']['attributes']['approval_status']) {
    //             case 0:
    //                 $status = 'approved';
    //                 break;
    //             case 2:
    //                 $status = 'rejected';
    //                 break;
    //             case 3:
    //                 $status = 'canceled';
    //                 break;
    //             case 1:
    //                 $status = 'approved';
    //                 break;
    //             default:
    //                 $status = 'default';
    //         }

    //         $reason = null;
    //         $concatenate = false;

    //         if ($log['properties']['attributes']['reason_to_reject_id'] != null) {
    //             $reason = $log['properties']['attributes']['reason_to_reject']['title'];
    //             $concatenate = true;
    //         }
    //         if ($log['properties']['attributes']['reason'] != null) {
    //             if ($concatenate) {
    //                 $reason = $reason . ' - ' . $log['properties']['attributes']['reason'];
    //             } else {
    //                 $reason = $log['properties']['attributes']['reason'];
    //             }
    //         }

    //         $retorno[] = [
    //             'type' => $status,
    //             'createdAt' => $log['created_at'],
    //             'description' => $log['description'],
    //             'causerUser' => $log['causer_object']['name'],
    //             'causerUserRole' => isset($log['causer_object']['role']) ? $log['causer_object']['role']['title'] : '',
    //             'createdUser' => $log['properties']['attributes']['user']['name'] ?? '',
    //             'motive' => $reason,
    //             'stage' => isset($log['properties']['old']['order']) ? $log['properties']['old']['order'] + 1 : '',
    //         ];
    //     }
    //     return $retorno;
    // }


    public function getAccountsPayableApprovalFlowLog($id, $requestInfo)
    {
        $approvalFlowLogs = AccountsPayableApprovalFlowLog::where('payment_request_id', $id)->orderBy('created_at', 'asc')->get();

        $totalStages = null;

        if (!is_null($approvalFlowLogs) && !is_null($approvalFlowLogs->first())) {
            $groupApprovalFlow = $approvalFlowLogs->first()->groupApprovalFlow();

            if (!is_null($groupApprovalFlow) && !is_null($groupApprovalFlow->first())) {
                $totalStages = strval($groupApprovalFlow->first()->approval_flow()->max('approval_flow.order'));
            }
        }

        $approvalFlowLogs->map(function ($item) use ($totalStages) {
            $item['totalStages'] = $totalStages;
            return $item;
        });

        $dataLogs = RouteAccountsApprovalFlowLog::collection($approvalFlowLogs);
        return $dataLogs->collection->toArray();
    }

    public function getBillingLogs($id, $requestInfo)
    {
        $billingLogs = BillingLog::where('billing_id', $id)->orderBy('created_at', 'asc')->get();

        $totalStages = null;

        if (!is_null($billingLogs) && !is_null($billingLogs->first())) {
            $maxOrder = HotelApprovalFlow::max('order');
            $totalStages = strval($maxOrder);
        }

        $billingLogs->map(function ($item) use ($totalStages) {
            $item['totalStages'] = $totalStages;
            return $item;
        });

        $dataLogs = RouteBillingLog::collection($billingLogs);
        return $dataLogs->collection->toArray();
    }

    public function getHotelLogs($id, $requestInfo)
    {
        $hotelLogs = HotelLog::where('hotel_id', $id)->orderBy('created_at', 'asc')->get();

        $dataLogs = RouteHotelLog::collection($hotelLogs);
        return $dataLogs->collection->toArray();
    }

    public function getLogPaymentRequestUpdate($id, $requestInfo)
    {
        $dataLog = LogActivity::where([
            ['log_name', 'payment_request'],
            ['subject_id', $id],
            ['description', 'updated']
        ])->get();

        $responseLog = [];

        $purchaseOrderOldArray = [];
        $purchaseOrderNewArray = [];
        foreach ($dataLog as $log) {
            if (array_key_exists('purchase_order', $log->properties['old'])) {
                foreach ($log->properties['old']['purchase_order'] as $key => $purchaseOrder) {
                    $installmentPurchase = [];
                    foreach ($purchaseOrder['purchase_order_installments'] as $key => $installment) {
                        $installmentPurchase = [
                            'id' => $installment['installment_purchase']['id'],
                            'amount_received' => $installment['amount_received'],
                            'purchase_order_id' => $installment['installment_purchase']['purchase_order_id'],
                            'parcel_number' => $installment['installment_purchase']['parcel_number'],
                            'portion_amount' => $installment['installment_purchase']['portion_amount'],
                            'due_date' => $installment['installment_purchase']['due_date'],
                            'note' => $installment['installment_purchase']['note'],
                            'percentage_discount' => $installment['installment_purchase']['percentage_discount'],
                            'money_discount' => $installment['installment_purchase']['money_discount'],
                            'invoice_received' => $installment['installment_purchase']['invoice_received'],
                            'invoice_paid' => $installment['installment_purchase']['invoice_paid'],
                            'payment_request_id' => $installment['installment_purchase']['payment_request_id'],
                            'amount_paid' => $installment['installment_purchase']['amount_paid'],
                        ];
                        $purchaseOrder['purchase_order_installments'][$key] = $installmentPurchase;
                    }
                    $purchaseOrderOldArray = [$purchaseOrder];
                }
            }

            if (array_key_exists('purchase_order', $log->properties['attributes'])) {
                foreach ($log->properties['attributes']['purchase_order'] as $key => $purchaseOrder) {
                    $installmentPurchase = [];
                    foreach ($purchaseOrder['purchase_order_installments'] as $key => $installment) {
                        $installmentPurchase = [
                            'id' => $installment['installment_purchase']['id'],
                            'amount_received' => $installment['amount_received'],
                            'purchase_order_id' => $installment['installment_purchase']['purchase_order_id'],
                            'parcel_number' => $installment['installment_purchase']['parcel_number'],
                            'portion_amount' => $installment['installment_purchase']['portion_amount'],
                            'due_date' => $installment['installment_purchase']['due_date'],
                            'note' => $installment['installment_purchase']['note'],
                            'percentage_discount' => $installment['installment_purchase']['percentage_discount'],
                            'money_discount' => $installment['installment_purchase']['money_discount'],
                            'invoice_received' => $installment['installment_purchase']['invoice_received'],
                            'invoice_paid' => $installment['installment_purchase']['invoice_paid'],
                            'payment_request_id' => $installment['installment_purchase']['payment_request_id'],
                            'amount_paid' => $installment['installment_purchase']['amount_paid'],
                        ];
                        $purchaseOrder['purchase_order_installments'][$key] = $installmentPurchase;
                    }
                    $purchaseOrderNewArray = [$purchaseOrder];
                }
            }

            array_push(
                $responseLog,
                [
                    'old' => $log->properties['old'],
                    'date-log' => $log->created_at,
                    'new' => $log->properties['attributes'],
                    'causer' => $log->causer_object,
                    'old-purchase-order' => $purchaseOrderOldArray,
                    'new-purchase-order' => $purchaseOrderNewArray
                ]
            );
        }
        return $responseLog;
    }

    public function getLogBillingUpdate($id, $requestInfo)
    {
        $dataLog = LogActivity::where([
            ['log_name', 'billing'],
            ['subject_id', $id],
            ['description', 'updated']
        ])->get();

        $responseLog = [];
        foreach ($dataLog as $log) {

            array_push(
                $responseLog,
                [
                    'old' => $log->properties['old'],
                    'date-log' => $log->created_at,
                    'new' => $log->properties['attributes'],
                    'causer' => $log->causer_object,
                ]
            );
        }
        return $responseLog;
    }

    public function getLogHotelUpdate($id, $requestInfo)
    {
        $dataLog = LogActivity::where([
            ['log_name', 'hotels'],
            ['subject_id', $id],
            ['description', 'updated']
        ])->get();

        $responseLog = [];
        foreach ($dataLog as $log) {

            array_push(
                $responseLog,
                [
                    'old' => $log->properties['old'],
                    'date-log' => $log->created_at,
                    'new' => $log->properties['attributes'],
                    'causer' => $log->causer_object,
                ]
            );
        }
        return $responseLog;
    }
}

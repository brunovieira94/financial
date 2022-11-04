<?php

namespace App\Services;

use App\Http\Resources\reports\RouteAccountsApprovalFlowLog;
use App\Http\Resources\reports\RouteBillingLog;
use App\Models\AccountsPayableApprovalFlow;
use App\Models\AccountsPayableApprovalFlowLog;
use App\Models\BillingLog;
use App\Models\ApprovalFlow;
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
        $dataLogs = RouteAccountsApprovalFlowLog::collection(AccountsPayableApprovalFlowLog::where('payment_request_id', $id)->orderBy('created_at', 'asc')->get());
        return $dataLogs->collection->toArray();
    }

    public function getBillingLogs($id, $requestInfo)
    {
        $dataLogs = RouteBillingLog::collection(BillingLog::where('billing_id', $id)->orderBy('created_at', 'asc')->get());
        return $dataLogs->collection->toArray();
    }
}

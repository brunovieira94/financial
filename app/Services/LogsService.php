<?php

namespace App\Services;

use App\Models\AccountsPayableApprovalFlow;
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
                    default:
                        $status = 'default';
                }

                $reason = null;
                $concatenate = false;

                if ($log['properties']['attributes']['reason_to_reject'] != null) {
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
                    'createdAt' => $log['created_at'],
                    'description' => $log['description'],
                    'causerUser' => $log['causer_object']['name'],
                    'causerUserRole' => $log['causer_object']['role']['title'],
                    'createdUser' => $log['properties']['attributes']['payment_request']['user']['name'] ?? null,
                    'motive' => $reason,
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
}

<?php

namespace App\Services;

use App\Models\NotificationCatalog;
use App\Models\NotificationCatalogHasRoles;
use App\Models\NotificationCatalogHasUsers;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestClean;
use App\Models\SupplyApprovalFlow;
use Illuminate\Support\Facades\Redis;

class NotificationService
{

    public static function sendEmail($data)
    {
        $queueID = uniqid();
        Redis::hSet($queueID, 'name', 'message-job');
        Redis::hSet($queueID, 'opts', '{}');
        Redis::hSet($queueID, 'delay', 0);
        Redis::hSet($queueID, 'processedOn', 'null');
        Redis::hSet($queueID, 'timestamp', 'null');
        Redis::hSet($queueID, 'priority', 0);
        Redis::hSet($queueID, 'data', json_encode($data));
        Redis::rpush('active', $queueID);
    }

    public static function generateDataSendRedisPaymentRequest($paymentRequestModel, $mails = [], $titleMail, $typeMail)
    {
        $company = [
            'name' =>  'empresa',
            'value' =>  $paymentRequestModel->company->trade_name ?? '',
        ];
        $provider = [
            'name' =>  'fornecedor',
            'value' =>  $paymentRequestModel->provider->trade_name ?? '',
        ];
        $paymentRequest = [
            'name' =>  'id',
            'value' =>  strval($paymentRequestModel->id) ?? '',
        ];
        $paymentRequestNetValue = [
            'name' =>  'valor',
            'value' =>  $paymentRequestModel->currency->currency_symbol . ' ' . $paymentRequestModel->net_value,
        ];
        return [
            'to' => $mails,
            'subject' => $titleMail,
            'type' => $typeMail,
            "args" => [
                $company,
                $paymentRequest,
                $paymentRequestNetValue,
                $provider,
            ]
        ];
    }

    public static function dailyMailPerUser($idPaymentRequest = [], $userMail)
    {
        $paymentRequests = PaymentRequestClean::withoutGlobalScopes()
            ->with(['provider', 'company', 'cost_center', 'currency'])
            ->whereIn('id', $idPaymentRequest)
            ->get();

        $data = [
            'to' => $userMail,
            'subject' => 'E-mail diário de contas a aprovar',
            'type' => 'daily-mail-user',
            'args' => []
        ];

        foreach ($paymentRequests as $paymentRequest) {
            $dataPaymentRequest = [
                'id' => $paymentRequest->id,
                'valor' => ($paymentRequest->currency->currency_symbol ?? '') . ' ' . ($paymentRequest->net_value ?? ''),
                'centroCusto' => $paymentRequest->cost_center->title ?? '',
                'fornecedor' => $paymentRequest->provider->trade_name ?? '',
                'empresa' => $paymentRequest->company->trade_name ?? '',
            ];
            array_push($data['args'], $dataPaymentRequest);
        }

        self::sendEmail($data);
    }

    public static function generateDataSendRedisPurchaseOrder($purchaseOrder, $mails = [], $titleMail, $typeMail, $approveUser, $paymentRequestId)
    {
        if (NotificationCatalog::where(['type' => $typeMail, 'active' => true, 'schedule' => 0])->exists()) {
            $notificationCatalog = NotificationCatalog::where(['type' => $typeMail, 'active' => true, 'schedule' => 0])->firstOrFail();

            $users = NotificationCatalogHasUsers::where('notification_catalog_id', $notificationCatalog->id)->with('user')->get();

            foreach ($users as $user) {
                if (!in_array($user->user->email, $mails, true)) {
                    array_push($mails, $user->user->email);
                }
            }

            $roles = NotificationCatalogHasRoles::where('notification_catalog_id', $notificationCatalog->id)->with('user')->get();

            foreach ($roles as $role) {
                foreach ($role->user as $roleUser) {
                    if (!in_array($roleUser->email, $mails, true)) {
                        array_push($mails, $roleUser->email);
                    }
                }
            }
            // Na hora
            $purchaseOrderID = [
                'name' =>  'id',
                'value' =>  strval($purchaseOrder->id) ?? '',
            ];

            $provider = [
                'name' =>  'fornecedor',
                'value' =>  $purchaseOrder->provider->trade_name ?? '',
            ];

            $costCenter = [
                'name' =>  'costCenter',
                'value' =>  '' ?? '',
            ];
            $causeUser = [
                'name' =>  'causerUser',
                'value' =>  $approveUser->name ?? '',
            ];
            $paymentRequestId = [
                'name' =>  'paymentRequestId',
                'value' =>  $paymentRequestId ?? '',
            ];

            $data = [
                'to' => $mails,
                'subject' => $titleMail,
                'type' => $typeMail,
                "args" => [
                    $purchaseOrderID,
                    $provider,
                    $costCenter,
                    $causeUser,
                    $paymentRequestId
                ]
            ];

            self::sendEmail($data);
        } else {
            return false;
        }
    }

    public static function dailyPurchaseOrderRenewal($purchaseOrders, $mails = [], $typeMail)
    {
        $data = [
            'to' => $mails,
            'subject' => 'E-mail diário pedidos de compra por renovar',
            'type' => $typeMail,
            'args' => []
        ];

        foreach ($purchaseOrders as $purchaseOrder) {
            $dataPurchaseOrder = [
                'id' => $purchaseOrder['purchase_order_id'],
                'fornecedor' => $purchaseOrder['provider_trade_name'] ?? '',
                'days_to_end' => $purchaseOrder['days_to_end'],
                'end_date' => $purchaseOrder['end_date']
            ];
            array_push($data['args'], $dataPurchaseOrder);
        }

        self::sendEmail($data);
    }

    public static function generateDataSendRedisResetPassword($mail = [], $titleMail, $typeMail, $name, $code)
    {
        $data = [
            'to' => $mail,
            'subject' => $titleMail,
            'type' => $typeMail,
            'args' => [
                [
                    'name' =>  'name',
                    'value' =>  $name ?? '',
                ],
                [
                    'name' =>  'code',
                    'value' =>  $code ?? '',
                ]
            ]
        ];
        self::sendEmail($data);
    }
}

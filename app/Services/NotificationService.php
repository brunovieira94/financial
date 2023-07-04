<?php

namespace App\Services;

use App\Models\ApprovalFlowSupply;
use App\Models\NotificationCatalog;
use App\Models\NotificationCatalogHasRoles;
use App\Models\NotificationCatalogHasUsers;
use App\Models\PaymentRequestClean;
use App\Models\SupplyApprovalFlow;

use Illuminate\Support\Facades\Redis;

class NotificationService
{
    // 24 hours = 1 day expiration time for each notification
    static $DEFAULT_MESSAGE_EXPIRATION_TIME = 24 * 60 * 60;

    public static function sendEmail($data, $expireMessage = true)
    {
        $queueID = uniqid();

        Redis::hSet($queueID, 'name', 'message-job');
        Redis::hSet($queueID, 'opts', '{}');
        Redis::hSet($queueID, 'delay', 0);
        Redis::hSet($queueID, 'processedOn', 'null');
        Redis::hSet($queueID, 'timestamp', 'null');
        Redis::hSet($queueID, 'priority', 0);
        Redis::hSet($queueID, 'data', json_encode($data));

        if ($expireMessage) {
            // when `$expireMessage` is true this message will only be on the queue
            // for a time corresponding of `self::$DEFAULT_MESSAGE_EXPIRATION_TIME` secs.
            Redis::expire($queueID, self::$DEFAULT_MESSAGE_EXPIRATION_TIME);
        }

        Redis::rpush('active', $queueID);
    }

    public static function generateDataSendRedisPaymentRequest($paymentRequestModel, $mails = [], $titleMail, $typeMail, $order, $maxOrder)
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
        $order = [
            'name' =>  'etapa',
            'value' =>  $order
        ];
        $maxOrder = [
            'name' =>  'totalEtapa',
            'value' =>  $maxOrder,
        ];

        $approverStage = [
            'name' =>  'nomeEtapa',
            'value' =>  $paymentRequestModel->approver_stage_first->title ?? ''
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
                $order,
                $maxOrder,
                $approverStage,
            ]
        ];
    }

    public static function dailyMailPerUser($idPaymentRequest = [], $userMail, $quantityPaymentRequest)
    {
        $paymentRequests = PaymentRequestClean::withoutGlobalScopes()
            ->with(['provider', 'company', 'cost_center', 'currency'])
            ->whereIn('id', $idPaymentRequest)
            ->get();

        $data = [
            'to' => $userMail,
            'subject' => 'E-mail diário de contas a aprovar',
            'type' => 'daily-mail-user',
            'args' => [
                [
                    'name' =>  'quantidadeRegistro',
                    'value' =>  $quantityPaymentRequest,
                ]
            ]
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

    public static function generateDataSendRedisPurchaseOrder($purchaseOrder, $mails = [], $titleMail, $typeMail, $approveUser, $paymentRequestId, $customText)
    {
        if (NotificationCatalog::where(['type' => $typeMail, 'active' => true, 'schedule' => 0])->exists()) {
            $mails = self::getMails($typeMail, $mails, $purchaseOrder);
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
            $customText = [
                'name' =>  'customText',
                'value' =>  $customText ?? '',
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
                    $paymentRequestId,
                    $customText
                ]
            ];

            self::sendEmail($data);
        } else {
            return false;
        }
    }

    public static function generateDataSendRedisPurchaseOrderPaymentRequest($paymentRequest, $purchaseOrderInfo, $mails = [], $titleMail, $typeMail, $approveUser)
    {
        if (NotificationCatalog::where(['type' => $typeMail, 'active' => true, 'schedule' => 0])->exists()) {
            $mails = self::getMails($typeMail, $mails, $purchaseOrderInfo);

            $purchaseOrderID = [
                'name' =>  'id',
                'value' =>  strval($purchaseOrderInfo->id) ?? '',
            ];

            $provider = [
                'name' =>  'fornecedor',
                'value' =>  $purchaseOrderInfo->provider->trade_name ?? '',
            ];

            $causeUser = [
                'name' =>  'causerUser',
                'value' =>  $approveUser->name ?? '',
            ];
            $paymentRequestId = [
                'name' =>  'idSoliciatacaoPagamento',
                'value' =>  $paymentRequest->id ?? '',
            ];
            $data = [
                'to' => $mails,
                'subject' => $titleMail,
                'type' => $typeMail,
                "args" => [
                    $purchaseOrderID,
                    $provider,
                    $causeUser,
                    $paymentRequestId,
                ]
            ];

            self::sendEmail($data);
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

    public static function getMails($typeMail, $mails, $purchaseOrder)
    {
        $notificationCatalog = NotificationCatalog::where(['type' => $typeMail, 'active' => true, 'schedule' => 0])->firstOrFail();

        $users = NotificationCatalogHasUsers::where('notification_catalog_id', $notificationCatalog->id)->with('user')->get();

        foreach ($users as $user) {
            if (!in_array($user->user->email, $mails, true)) {
                array_push($mails, $user->user->email);
            }
        }

        $costCenterId = [];
        $maxPercentage = 0;
        $constCenterEqual = false;

        foreach ($purchaseOrder->cost_centers as $costCenter) {
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
        if ($typeMail == 'purchase-order-to-approve') {
            $order = SupplyApprovalFlow::where('id_purchase_order', $purchaseOrder->id)->get('order')->pluck('order');
            $approvalFlowOrders = ApprovalFlowSupply::where('order', $order)
                ->get('role_id')->pluck('role_id');

            $roles = NotificationCatalogHasRoles::where('notification_catalog_id', $notificationCatalog->id)
                ->whereIn('role_id', $approvalFlowOrders)
                ->with('user', 'role')->get();
        } else {
            $roles = NotificationCatalogHasRoles::where('notification_catalog_id', $notificationCatalog->id)
                ->with('user', 'role')->get();
        }

        foreach ($roles as $role) {
            if ($role->role->filter_cost_center_supply) {
                foreach ($role->user as $roleUser) {
                    foreach ($roleUser->cost_center as $roleUserCostCenter) {
                        if ($constCenterEqual) {
                            if ($roleUserCostCenter->id == $costCenterId[0]) {
                                if (!in_array($roleUser->email, $mails, true)) {
                                    array_push($mails, $roleUser->email);
                                }
                            }
                        } else {
                            if (in_array($roleUserCostCenter->id, $costCenterId, true)) {
                                if (!in_array($roleUser->email, $mails, true)) {
                                    array_push($mails, $roleUser->email);
                                }
                            }
                        }
                    }
                }
            } else {
                foreach ($role->user as $roleUser) {
                    if (!in_array($roleUser->email, $mails, true)) {
                        array_push($mails, $roleUser->email);
                    }
                }
            }
        }

        return $mails;
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

    public static function generateDataSendImportInstallmentsPaidReport($mail = [], $titleMail, $typeMail, $fileName, $failures, $errors = [])
    {
        $args = [
            [
                'name' => 'file',
                'value' => $fileName,
            ],
            [
                'name' => 'failures',
                'value' => $failures,
            ],
            [
                'name' => 'errors',
                'value' => $errors,
            ]
        ];

        $data = [
            'to' => $mail,
            'subject' => $titleMail,
            'type' => $typeMail,
            'args' => $args,
        ];

        self::sendEmail($data, true);
    }

    public static function generateDataSendRedisAttachment($mails = [], $typeMail, $link, $initialDate, $finalDate)
    {
        $initialDate = [
            'name' =>  'dataInicial',
            'value' =>  $initialDate,
        ];
        $finalDate = [
            'name' =>  'dataFinal',
            'value' =>  $finalDate,
        ];
        $link = [
            'name' =>  'link',
            'value' =>  $link,
        ];

        return [
            'to' => $mails,
            'type' => $typeMail,
            "args" => [
                $initialDate,
                $finalDate,
                $link
            ]
        ];
    }

    public static function mailTest($mails = [])
    {
        self::sendEmail([
            'to' => $mails,
            'subject' => 'test',
            'type' => 'test',
        ]);
    }
}

<?php

namespace App\Services;

use Config;

use App\Http\Resources\Integrations\ApprovedPaymentRequestsSAPResource;
use App\Http\Resources\Integrations\PaidInstallmentsSAPResource;
use App\Models\IntegrationClient;
use App\Models\PaymentRequestHasInstallments;
use App\Models\PaymentRequest;
use Hash;

class IntegrationService
{
    private $paymentRequestHasInstallments;
    private $paymentRequest;

    private $withPaidInstallments = [
        'payment_request',
        'payment_request.provider',
        'payment_request.chart_of_accounts',
        'payment_request.currency',
        'group_payment',
        'bank_account_provider',
        'other_payments',
        'other_payments.bank_account_company',
        'other_payments.exchange_rates',
        'other_payments.exchange_rates.currency',
        'cnab_generated_installment',
        'cnab_generated_installment.generated_cnab',
        'cnab_generated_installment.generated_cnab.company',
    ];

    private $withApprovedPaymentRequests = [
        'approval',
        'log_approval_flow',
        'company',
        'installments',
        'currency',
        'chart_of_accounts',
        'cost_center',
        'provider',
        'provider.city',
        'provider.city.state',
        'provider.city.state.country',
        'purchase_order',
        'purchase_order.purchase_order',
        'purchase_order.purchase_order.provider',
        'purchase_order.purchase_order.provider.chart_of_account',
        'purchase_order.purchase_order.cost_centers',
    ];

    public function __construct(PaymentRequestHasInstallments $paymentRequestHasInstallments, PaymentRequest $paymentRequest)
    {
        $this->paymentRequestHasInstallments = $paymentRequestHasInstallments;
        $this->paymentRequest = $paymentRequest;
    }

    public function sapBillsApproved($requestInfo)
    {
        $bills = $this->paymentRequest::with($this->withApprovedPaymentRequests)
            ->whereHas('approval', fn ($query) => $query->where('status', Config::get('constants.status.approved')));

        $bills = $this->filterByDateCreated($bills, $requestInfo);

        if (array_key_exists('date_from', $requestInfo)) {
            $bills = $bills->whereHas('log_approval_flow', fn ($q) => $q->where('updated_at', '>=', $requestInfo['date_from']));
        }
        if (array_key_exists('date_to', $requestInfo)) {
            $bills = $bills->whereHas('log_approval_flow', fn ($q) => $q->where('updated_at', '<=', $requestInfo['date_to']));
        }

        return ApprovedPaymentRequestsSAPResource::collection($bills->get())->collection->toArray();
    }

    public function sapInstallmentsPaid($requestInfo)
    {
        $installments = $this->paymentRequestHasInstallments::with($this->withPaidInstallments)
            ->whereHas('payment_request', function ($query) use (&$requestInfo) {
                $query->whereHas('approval', fn ($query) => $query->where('status', Config::get('constants.status.paid out')));
                $query = $this->filterByDateCreated($query, $requestInfo);
            });

        if (array_key_exists('date_from', $requestInfo) || array_key_exists('date_to', $requestInfo)) {
            $installments = $installments
                ->whereHas('cnab_generated_installment', function ($query) use (&$requestInfo) {
                    $query->whereHas('generated_cnab', function ($query) use (&$requestInfo) {
                        if (array_key_exists('date_from', $requestInfo)) {
                            $query->where('file_date', '>=', $requestInfo['date_from']);
                        }
                        if (array_key_exists('date_to', $requestInfo)) {
                            $query->where('file_date', '<=', $requestInfo['date_to']);
                        }
                    });
                })
                ->orWhereHas('other_payments', function ($query) use (&$requestInfo) {
                    if (array_key_exists('date_from', $requestInfo)) {
                        $query->where('payment_date', '>=', $requestInfo['date_from']);
                    }
                    if (array_key_exists('date_to', $requestInfo)) {
                        $query->where('payment_date', '<=', $requestInfo['date_to']);
                    }
                });
        }

        return PaidInstallmentsSAPResource::collection($installments->get())->collection->toArray();
    }


    private function filterByDateCreated($query, $requestInfo)
    {
        if (array_key_exists('date_created_from', $requestInfo)) {
            $query = $query->where('created_at', '>=', $requestInfo['date_created_from']);
        }
        if (array_key_exists('date_created_to', $requestInfo)) {
            $query = $query->where('created_at', '<=', $requestInfo['date_created_to']);
        }
        return $query;
    }

    public function storeClient($requestInfo)
    {
        if (array_key_exists('cid', $requestInfo) && array_key_exists('cpass', $requestInfo)) {
            $client = IntegrationClient::create(["client_id" => $requestInfo['cid'], "client_secret" =>  Hash::make($requestInfo['cpass'])]);
            return response()->json($client, 201);
        } else {
            return response()->json(["error" => "Bad Request"], 400);
        }
    }
}

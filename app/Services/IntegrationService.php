<?php

namespace App\Services;

use Config;
use Hash;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\Integrations\ApprovedPaymentRequestsSAPResource;
use App\Http\Resources\Integrations\PaidInstallmentsSAPResource;
use App\Models\IntegrationClient;
use App\Models\PaymentRequestHasInstallments;
use App\Models\PaymentRequest;
use App\Services\Utils;

class IntegrationService
{
    private $paymentRequestHasInstallments;
    private $paymentRequest;
    private $integration;

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

    public function __construct(PaymentRequestHasInstallments $paymentRequestHasInstallments, PaymentRequest $paymentRequest, IntegrationClient $integration)
    {
        $this->paymentRequestHasInstallments = $paymentRequestHasInstallments;
        $this->paymentRequest = $paymentRequest;
        $this->integration = $integration;
    }

    public function sapBillsApproved($requestInfo)
    {
        $bills = $this->paymentRequest::with($this->withApprovedPaymentRequests)
            ->whereHas('approval', fn ($q) => $q->where('status', Config::get('constants.status.approved')));

        $bills = $this->filterByDateCreated($bills, $requestInfo);

        $bills = $bills->get()->filter(Utils::approvalDatePaymentRequestFilter([
            'from' => array_key_exists('date_from', $requestInfo) ? $requestInfo['date_from'] : null,
            'to' => array_key_exists('date_to', $requestInfo) ? $requestInfo['date_to'] : null,
        ]), true);

        return array_values(ApprovedPaymentRequestsSAPResource::collection($bills)->collection->toArray());
    }

    public function sapInstallmentsPaid($requestInfo)
    {
        $installments = $this->paymentRequestHasInstallments::with($this->withPaidInstallments)
            ->whereHas('payment_request', function ($query) use (&$requestInfo) {
                $query->whereHas('approval', fn ($q) => $q->where('status', Config::get('constants.status.paid out')));
                $query = $this->filterByDateCreated($query, $requestInfo);
            });

        $installments = $installments
            ->whereHas('cnab_generated_installment', function ($query) use (&$requestInfo) {
                $query->whereHas('generated_cnab', function ($q) use (&$requestInfo) {
                    if (array_key_exists('date_from', $requestInfo))
                        $q->where('file_date', '>=', $requestInfo['date_from'] . ' 00:00:00');
                    if (array_key_exists('date_to', $requestInfo))
                        $q->where('file_date', '<=', $requestInfo['date_to'] . ' 23:59:59');
                });
            })
            ->orWhereHas('other_payments', function ($query) use (&$requestInfo) {
                if (array_key_exists('date_from', $requestInfo))
                    $query->where('payment_date', '>=', $requestInfo['date_from']);
                if (array_key_exists('date_to', $requestInfo))
                    $query->where('payment_date', '<=', $requestInfo['date_to']);
            })
            ->orWhere(function (Builder $query) use (&$requestInfo) {
                if (array_key_exists('date_from', $requestInfo))
                    $query->where('payment_made_date', '>=', $requestInfo['date_to']);
                if (array_key_exists('date_to', $requestInfo))
                    $query->where('payment_made_date', '<=', $requestInfo['date_to']);
            });

        return array_values(PaidInstallmentsSAPResource::collection($installments->get())->collection->toArray());
    }


    private function filterByDateCreated($query, $requestInfo)
    {
        if (array_key_exists('date_created_from', $requestInfo))
            $query = $query->where('created_at', '>=', $requestInfo['date_created_from']);
        if (array_key_exists('date_created_to', $requestInfo))
            $query = $query->where('created_at', '<=', $requestInfo['date_created_to']);
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

    public function getAllClient($requestInfo)
    {
        $integration = Utils::search($this->integration, $requestInfo);
        return Utils::pagination($integration, $requestInfo);
    }

    public function getClient($id)
    {
        return $this->integration->findOrFail($id);
    }

    public function updateClient($requestInfo, $id)
    {
        $integration = $this->integration->findOrFail($id);
        $integration->fill($requestInfo);

        if (array_key_exists('cpass', $requestInfo)) {
            $integration->client_secret = Hash::make($requestInfo['cpass']);
        }

        if (array_key_exists('cid', $requestInfo)) {
            $integration->client_id = $requestInfo['cid'];
        }

        $integration->save();
        return $integration;
    }

    public function deleteClient($id)
    {
        $this->integration->findOrFail($id)->delete();
        return true;
    }
}

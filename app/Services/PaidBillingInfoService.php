<?php

namespace App\Services;


use App\Models\PaidBillingInfo;

class PaidBillingInfoService
{

    private $paidBillingInfo;

    private $with = ['user'];

    public function __construct(PaidBillingInfo $paidBillingInfo)
    {
        $this->paidBillingInfo = $paidBillingInfo;
    }

    public function getAllPaidBillingInfo($requestInfo)
    {
        $paidBillingInfo = Utils::search($this->paidBillingInfo, $requestInfo);
        if (array_key_exists('created_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['created_at'])) {
                $paidBillingInfo->where('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['created_at'])) {
                $paidBillingInfo->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
            }
            if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                $paidBillingInfo->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['pay_date'])) {
                $paidBillingInfo->where('pay_date', '>=', $requestInfo['pay_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['pay_date'])) {
                $paidBillingInfo->where('pay_date', '<=', $requestInfo['pay_date']['to']);
            }
            if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                $paidBillingInfo->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if (array_key_exists('form_of_payment', $requestInfo)) {
            $paidBillingInfo->where('form_of_payment', $requestInfo['form_of_payment']);
        }
        if (array_key_exists('cnpj', $requestInfo)) {
            $paidBillingInfo->where('cnpj_hotel', $requestInfo['cnpj']);
        }
        if (array_key_exists('service_id', $requestInfo)) {
            $paidBillingInfo->where('service_id', $requestInfo['service_id']);
        }
        if (array_key_exists('reserve', $requestInfo)) {
            $paidBillingInfo->where('reserve', $requestInfo['reserve']);
        }
        if (array_key_exists('client_name', $requestInfo)) {
            $paidBillingInfo->where('client_name', $requestInfo['client_name']);
        }
        return Utils::pagination($paidBillingInfo->with($this->with), $requestInfo);
    }

    public function getPaidBillingInfo($id)
    {
        return $this->paidBillingInfo->with($this->with)->findOrFail($id);
    }

    public function deletePaidBillingInfo($id)
    {
        $this->paidBillingInfo->findOrFail($id)->delete();
        return true;
    }

    public function getPaidBillingInfoClients()
    {
        $clients = $this->paidBillingInfo->where('client_name','!=',null)->distinct()->pluck('client_name');
        return $clients;
    }
}

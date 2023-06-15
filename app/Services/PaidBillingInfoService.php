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
        $paidBillingInfo = Utils::baseFilterPaidBillingInfo($paidBillingInfo, $requestInfo);
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

<?php

namespace App\Services;


use App\Models\Billing;
use App\Models\Cangooroo;

class BillingService
{

    private $billing;
    private $cangoorooService;
    private $cangooroo;

    private $with = ['user', 'cangooroo'];

    public function __construct(Billing $billing, CangoorooService $cangoorooService, Cangooroo $cangooroo)
    {
        $this->billing = $billing;
        $this->cangooroo = $cangooroo;
        $this->cangoorooService = $cangoorooService;
    }

    public function getAllBilling($requestInfo)
    {
        $billing = Utils::search($this->billing, $requestInfo);
        return Utils::pagination($billing->with($this->with), $requestInfo);
    }

    public function getBilling($id)
    {
        $billing = $this->billing->findOrFail($id);
        $this->cangoorooService->updateCangoorooData($billing['cangooroo_booking_id']);
        return $this->billing->with($this->with)->findOrFail($id);
    }

    public function postBilling($billingInfo)
    {
        $billing = new Billing;
        $billingInfo['user_id'] = auth()->user()->id;
        $cangooroo = new Cangooroo();
        $cangooroo = $cangooroo->create(["booking_id" => $billingInfo['cangooroo_booking_id']]);
        $this->cangoorooService->updateCangoorooData($billingInfo['cangooroo_booking_id']);
        $billing = $billing->create($billingInfo);
        return $this->billing->with($this->with)->findOrFail($billing->id);
    }

    public function putBilling($id, $billingInfo)
    {
        $billing = $this->billing->findOrFail($id);
        $billing->fill($billingInfo)->save();
        $this->cangoorooService->updateCangoorooData($billing['cangooroo_booking_id']);
        return $this->billing->with($this->with)->findOrFail($billing->id);
    }

    public function deleteBilling($id)
    {
        $this->billing->findOrFail($id)->delete();
        return true;
    }
}

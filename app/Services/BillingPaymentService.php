<?php

namespace App\Services;

use App\Models\BillingPayment;
use Config;

class BillingPaymentService
{
    private $billingPayment;
    private $with = ['billings', 'hotel'];
    public function __construct(BillingPayment $billingPayment)
    {
        $this->billingPayment = $billingPayment;
    }

    public function getAllBillingPayment($requestInfo)
    {
        $billingPayment = Utils::search($this->billingPayment, $requestInfo);
        if (array_key_exists('id_hotel_cangooroo', $requestInfo)) {
            $billingPayment->whereHas('cangooroo', function ($query) use ($requestInfo) {
                $query->where('hotel_id', $requestInfo['id_hotel_cangooroo']);
            });
        }
        if (array_key_exists('created_at', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['created_at'])) {
                $billingPayment->where('created_at', '>=', $requestInfo['created_at']['from']);
            }
            if (array_key_exists('to', $requestInfo['created_at'])) {
                $billingPayment->where('created_at', '<=', date("Y-m-d", strtotime("+1 days", strtotime($requestInfo['created_at']['to']))));
            }
            if (!array_key_exists('to', $requestInfo['created_at']) && !array_key_exists('from', $requestInfo['created_at'])) {
                $billingPayment->whereBetween('created_at', [now()->addMonths(-1), now()]);
            }
        }
        if (array_key_exists('pay_date', $requestInfo)) {
            if (array_key_exists('from', $requestInfo['pay_date'])) {
                $billingPayment->where('pay_date', '>=', $requestInfo['pay_date']['from']);
            }
            if (array_key_exists('to', $requestInfo['pay_date'])) {
                $billingPayment->where('pay_date', '<=', $requestInfo['pay_date']['to']);
            }
            if (!array_key_exists('to', $requestInfo['pay_date']) && !array_key_exists('from', $requestInfo['pay_date'])) {
                $billingPayment->whereBetween('pay_date', [now(), now()->addMonths(1)]);
            }
        }
        if (array_key_exists('status', $requestInfo)) {
            $billingPayment->where('status', $requestInfo['status']);
        }
        if (array_key_exists('form_of_payment', $requestInfo)) {
            $billingPayment->where('form_of_payment', $requestInfo['form_of_payment']);
        }
        return Utils::pagination($billingPayment->with($this->with), $requestInfo);
    }

    public function getBillingPayment($id)
    {
        return $this->billingPayment->with($this->with)->findOrFail($id);
    }
}

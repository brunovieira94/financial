<?php

namespace App\Services;
use App\Models\PaymentMethod;

class PaymentMethodService
{
    private $paymentMethod;
    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getAllPaymentMethod($requestInfo)
    {
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return $this->paymentMethod->orderBy($orderBy, $order)->paginate($perPage);
    }

    public function getPaymentMethod($id)
    {
        return $this->paymentMethod->findOrFail($id);
    }

    public function postPaymentMethod($paymentMethodInfo)
    {
        $paymentMethod = new PaymentMethod;
        return $paymentMethod->create($paymentMethodInfo);
    }

    public function putPaymentMethod($id, $paymentMethodInfo)
    {
        $paymentMethod = $this->paymentMethod->findOrFail($id);
        $paymentMethod->fill($paymentMethodInfo)->save();
        return $paymentMethod;
    }

    public function deletePaymentMethod($id)
    {
        $this->paymentMethod->findOrFail($id)->delete();
        return true;
    }

}


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
        $paymentMethod = Utils::search($this->paymentMethod,$requestInfo);
        return Utils::pagination($paymentMethod,$requestInfo);
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


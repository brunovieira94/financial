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

    public function getAllPaymentMethod()
    {
        return $this->paymentMethod->get();
    }

    public function getPaymentMethod($id)
    {
        return $this->paymentMethod->findOrFail($id);
    }

    public function postPaymentMethod($titlePaymentMethod)
    {
        $paymentMethod = new PaymentMethod;
        $paymentMethod->title = $titlePaymentMethod;
        $paymentMethod->save();
        return $paymentMethod;
    }

    public function putPaymentMethod($id, $titlePaymentMethod)
    {
        $paymentMethod = $this->paymentMethod->findOrFail($id);
        $paymentMethod->title = $titlePaymentMethod;
        $paymentMethod->save();
        return $paymentMethod;
    }

    public function deletePaymentMethod($id)
    {
        $this->paymentMethod->findOrFail($id)->delete();
        return true;
    }

}


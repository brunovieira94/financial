<?php

namespace App\Services;
use App\Models\PaymentType;

class PaymentTypeService
{
    private $paymentType;
    public function __construct(PaymentType $paymentType)
    {
        $this->paymentType = $paymentType;
    }

    public function getAllPaymentType($requestInfo)
    {
        $paymentType = Utils::search($this->paymentType,$requestInfo);
        return Utils::pagination($paymentType,$requestInfo);
    }

    public function getPaymentType($id)
    {
      return $this->paymentType->findOrFail($id);
    }

    public function postPaymentType($paymentTypeInfo)
    {
        $paymentType = new PaymentType;
        return $paymentType->create($paymentTypeInfo);
    }

    public function putPaymentType($id, $paymentTypeInfo)
    {
        $paymentType = $this->paymentType->findOrFail($id);
        $paymentType->fill($paymentTypeInfo)->save();
        return $paymentType;
    }

    public function deletePaymentType($id)
    {
      $this->paymentType->findOrFail($id)->delete();
      return true;
    }

}

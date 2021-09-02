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

    public function getAllPaymentType()
    {
        return $this->paymentType->get();
    }

    public function getPaymentType($id)
    {
      return $this->paymentType->findOrFail($id);
    }

<<<<<<< HEAD
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
=======
    public function postPaymentType($titlePaymentType)
    {
      $paymentType = new PaymentType;
      $paymentType->title = $titlePaymentType;
      $paymentType->save();
      return $paymentType;
    }

    public function putPaymentType($id, $titlePaymentType)
    {
      $paymentType = $this->paymentType->findOrFail($id);
      $paymentType->title = $titlePaymentType;
      $paymentType->save();
      return $paymentType;
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
    }

    public function deletePaymentType($id)
    {
      $this->paymentType->findOrFail($id)->delete();
      return true;
    }

}

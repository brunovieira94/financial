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
    }

    public function deletePaymentType($id)
    {
      $this->paymentType->findOrFail($id)->delete();
      return true;
    }

}

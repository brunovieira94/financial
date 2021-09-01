<?php

namespace App\Services;
use App\Models\PaymentType;

class PaymentTypeService
{

    public function getAllPaymentType()
    {
      $paymentTypes = PaymentType::get();
      return $paymentTypes;
    }

    public function getPaymentType($id)
    {
      $paymentType = PaymentType::findOrFail($id);
      return $paymentType;
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
      $paymentType = PaymentType::findOrFail($id);
      $paymentType->title = $titlePaymentType;
      $paymentType->save();
      return $paymentType;
    }

    public function deletePaymentType($id)
    {
      PaymentType::findOrFail($id)->delete();
      return true;
    }

}

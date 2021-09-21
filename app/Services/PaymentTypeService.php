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
        $orderBy = $requestInfo['orderBy'] ?? Utils::defaultOrderBy;
        $order = $requestInfo['order'] ?? Utils::defaultOrder;
        $perPage = $requestInfo['perPage'] ?? Utils::defaultPerPage;
        return $this->paymentType->orderBy($orderBy, $order)->paginate($perPage);
        return $this->paymentType->get();
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

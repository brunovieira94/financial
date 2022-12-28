<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestHasInstallmentsThatHaveOtherPayments extends Model
{
    protected $table = 'payment_request_installments_have_other_payments';
    protected $fillable = ['other_payment_id', 'payment_request_installment_id'];
    public $timestamps = false;

    public function payment_request_installment()
    {
        return $this->hasOne(PaymentRequestHasInstallments::class, 'id', 'payment_request_installment_id');
    }

    public function other_payment()
    {
        return $this->hasOne(OtherPayment::class, 'id', 'other_payment_id');
    }
}

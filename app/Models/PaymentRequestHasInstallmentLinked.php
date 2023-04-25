<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestHasInstallmentLinked extends Model
{
    protected $table='payment_requests_installments_linked';
    public $timestamps = false;
    protected $fillable = ['payment_requests_installment_id', 'payment_request_id'];
    protected $hidden = ['id', 'payment_requests_installment_id', 'payment_request_id'];

    public function installments()
    {
        return $this->hasOne(PaymentRequestHasInstallments::class, 'id', 'payment_requests_installment_id');
    }

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id');
    }

}

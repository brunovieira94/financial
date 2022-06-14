<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnabPaymentRequestsHasInstallments extends Model
{
    protected $table='cnab_payment_requests_has_installments';
    public $timestamps = false;
    protected $fillable = ['payment_request_id', 'installment_id', 'cnab_generated_id', 'cnab_generated_has_payment_requests_id'];
    protected $hidden = ['pivot', 'id', 'payment_request_id', 'cnab_generated_id', 'installment_id','cnab_generated_has_payment_requests_id' ];

    public function installment()
    {
        return $this->hasOne(PaymentRequestHasInstallments::class, 'id', 'installment_id');
    }

    public function generated_cnab()
    {
        return $this->hasOne(CnabGenerated::class, 'id', 'cnab_generated_id');
    }

}

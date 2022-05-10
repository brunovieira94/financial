<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestHasPurchaseOrderInstallments extends Model
{
    protected $table='payment_request_has_purchase_order_installments';
    public $timestamps = false;
    protected $fillable = ['payment_request_has_purchase_order_id', 'payment_request_id', 'purchase_order_has_installments_id'];

    public function installment_purchase()
    {
        return $this->hasOne(PurchaseOrderHasInstallments::class, 'id', 'purchase_order_has_installments_id');
    }

}

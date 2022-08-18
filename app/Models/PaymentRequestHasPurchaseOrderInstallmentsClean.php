<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestHasPurchaseOrderInstallmentsClean extends Model
{
    protected $table='payment_request_has_purchase_order_installments';
    public $timestamps = false;
    protected $fillable = ['amount_received', 'payment_request_has_purchase_order_id', 'payment_request_id', 'purchase_order_has_installments_id'];
    protected $hidden = ['id', 'payment_request_has_purchase_order_id', 'payment_request_id', 'purchase_order_has_installments_id'];

    public function installment_purchase()
    {
        return $this->hasOne(PurchaseOrderHasInstallments::class, 'id', 'purchase_order_has_installments_id');
    }

}

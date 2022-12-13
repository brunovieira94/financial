<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestHasPurchaseOrders extends Model
{
    protected $table = 'payment_request_has_purchase_orders';
    public $timestamps = false;
    protected $fillable = ['payment_request_id', 'purchase_order_id', 'reviewed'];
    protected $hidden = [/*'payment_request_id', 'purchase_order_id',*/'id'];

    public function purchase_order()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'purchase_order_id')->with(['products', 'services', 'currency']);
    }

    public function purchase_order_installments()
    {
        return $this->hasMany(PaymentRequestHasPurchaseOrderInstallments::class, 'payment_request_has_purchase_order_id', 'id')->with('installment_purchase');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestHasPurchaseOrders extends Model
{
    protected $table='payment_request_has_purchase_orders';
    public $timestamps = false;
    protected $fillable = ['payment_request_id', 'purchase_order_id'];

}

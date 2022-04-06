<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHasPurchaseRequests extends Model
{
    protected $table='purchase_order_has_purchase_requests';
    public $timestamps = false;
    protected $fillable = ['purchase_order_id', 'purchase_request_id'];
    protected $hidden = ['purchase_order_id', 'purchase_request_id'];

    public function purchase_request()
    {
        return $this->hasOne(PurchaseRequest::class, 'id', 'purchase_request_id');
    }
}

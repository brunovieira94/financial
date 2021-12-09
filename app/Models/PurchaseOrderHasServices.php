<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHasServices extends Model
{
    protected $table='purchase_order_has_services';
    public $timestamps = false;
    protected $fillable = ['purchase_order_id', 'service_id', 'unitary_value', 'initial_date', 'end_date', 'automatic_renovation', 'notice_time_to_renew'];
    protected $hidden = ['purchase_order_id', 'service_id'];

    public function service()
    {
        return $this->hasOne(Service::class, 'id', 'service_id');
    }
}

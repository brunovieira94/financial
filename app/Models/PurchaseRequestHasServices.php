<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestHasServices extends Model
{
    protected $table='purchase_request_has_services';
    public $timestamps = false;
    protected $fillable = ['purchase_request_id', 'service_id', 'contract_duration'];
    protected $hidden = ['purchase_request_id', 'service_id'];

    public function service()
    {
        return $this->hasOne(Service::class, 'id', 'service_id');
    }
}

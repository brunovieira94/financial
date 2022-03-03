<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestHasCostCenters extends Model
{
    protected $table='purchase_request_has_cost_centers';
    public $timestamps = false;
    protected $fillable = ['purchase_request_id', 'cost_center_id', 'percentage'];
    protected $hidden = ['purchase_request_id', 'cost_center_id'];

    public function cost_center()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id');
    }
}

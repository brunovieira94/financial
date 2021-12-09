<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHasCostCenters extends Model
{
    protected $table='purchase_order_has_cost_centers';
    public $timestamps = false;
    protected $fillable = ['purchase_order_id', 'cost_center_id', 'percentage'];
    protected $hidden = ['purchase_order_id', 'cost_center_id'];

    public function cost_center()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id');
    }
}

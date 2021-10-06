<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHasCostCenters extends Model
{
    protected $table='business_has_cost_centers';
    public $timestamps = false;
    protected $fillable = ['business_id', 'cost_center_id', 'user_id'];
    protected $hidden = ['pivot', 'id', 'business_id', 'cost_center_id', 'user_id'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function costCenter()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id');
    }

}

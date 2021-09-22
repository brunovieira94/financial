<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHasCostCenters extends Model
{
    protected $table='business_has_cost_centers';
    public $timestamps = false;
    protected $fillable = ['business_id', 'cost_center_id', 'user_id'];
}

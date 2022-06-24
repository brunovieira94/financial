<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserHasCostCenter extends Model
{
    protected $fillable = ['user_id', 'cost_center_id'];
    protected $table= 'user_has_cost_centers';
}

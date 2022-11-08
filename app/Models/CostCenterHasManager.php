<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostCenterHasManager extends Model
{
    protected $table = 'cost_center_has_manager';
    public $timestamps = false;
    protected $fillable = ['cost_center_id', 'manager_user_id'];
    protected $hidden = ['id', 'cost_center_id', 'manager_user_id'];

    public function cost_center()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id');
    }

    public function manager()
    {
        return $this->hasOne(User::class, 'id', 'manager_user_id');
    }
}

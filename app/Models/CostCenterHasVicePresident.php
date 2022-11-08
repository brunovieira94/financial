<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostCenterHasVicePresident extends Model
{
    protected $table = 'cost_center_has_vice_president';
    public $timestamps = false;
    protected $fillable = ['cost_center_id', 'vice_president_user_id'];
    protected $hidden = ['id', 'cost_center_id', 'vice_president_user_id'];

    public function cost_center()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id');
    }

    public function vice_president()
    {
        return $this->hasOne(User::class, 'id', 'vice_president_user_id');
    }
}

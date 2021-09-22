<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use SoftDeletes;
    protected $table='business';
    protected $hidden = ['pivot'];
    protected $fillable = ['company_id', 'name'];

    public function user()
    {
        return $this->belongsToMany(User::class, 'business_has_cost_centers', 'business_id', 'user_id');
    }

    public function costCenter()
    {
        return $this->belongsToMany(CostCenter::class, 'business_has_cost_centers', 'business_id', 'cost_center_id');
    }
}



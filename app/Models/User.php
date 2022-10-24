<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use softDeletes;
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'extension',
        'email',
        'password',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password','pivot', 'role_id'
    ];

    public function cost_center()
    {
        return $this->belongsToMany(CostCenter::class, 'user_has_cost_centers', 'user_id', 'cost_center_id');
    }

    public function business()
    {
        return $this->belongsToMany(Business::class, 'user_has_business', 'user_id', 'business_id');
    }

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }
}

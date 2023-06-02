<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;

class UserAuth extends Authenticatable
{
    use softDeletes;
    use HasApiTokens, HasFactory, Notifiable;

    use SoftDeletes;
    protected $table='users';

    protected $fillable = [
        'name',
        'phone',
        'extension',
        'email',
        'password',
        'status',
        'logged_user_id',
        'return_date',
        'role_id',
        'email_account_approval_notification',
        'daily_notification_accounts_approval_mail',
        'temporary',
    ];

    protected $hidden = [
        'password', 'pivot',
    ];

    protected $appends = ['role_id', 'cost_centers_logged'];

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

    public function additional_users()
    {
        return $this->belongsToMany(User::class, 'additional_users', 'user_id', 'user_additional_id');
    }

    public function getRoleIdAttribute()
    {
        if ($this->logged_user_id == null) {
            return DB::table('users')->where('id', $this->id)->get('role_id')[0]->role_id;
        } else {
            return User::withTrashed()->find($this->logged_user_id)->role_id;
        }
    }

    public function getCostCentersLoggedAttribute()
    {
        if ($this->logged_user_id == null) {
            return null;
        } else {
            $costCentersIDs = UserHasCostCenter::where('user_id', $this->logged_user_id)->get('id');
            return CostCenter::whereIn('id', $costCentersIDs->toArray())->get();
        }
    }

    public function filters()
    {
        return $this->hasMany(UserHasSavedFilter::class, 'user_id', 'id');
    }
}

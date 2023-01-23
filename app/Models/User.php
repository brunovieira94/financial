<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class User extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'users';

    public function tapActivity(Activity $activity, string $eventName)
    {
        if (auth()->user()) {
            $user = auth()->user() ?? '';
            $user->role = Role::findOrFail($user->role_id) ?? '';
            $activity->causer_id = $user->id ?? '';
            $activity->causer_object = $user ?? '';
        }
    }

    use SoftDeletes;
    protected $table = 'users';

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
    ];

    protected $hidden = [
        'password', 'pivot',
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

    public function additional_users()
    {
        return $this->belongsToMany(User::class, 'additional_users', 'user_id', 'user_additional_id');
    }
}

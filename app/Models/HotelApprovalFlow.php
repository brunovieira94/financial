<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use App\Models\Role;

class HotelApprovalFlow extends Model
{
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'hotel_approval_flow';
    public function tapActivity(Activity $activity)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table = 'hotel_approval_flow';
    protected $fillable = ['order', 'role_id'];
    protected $hidden = ['role_id'];

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id')->withTrashed();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use App\Models\Role;

class ApprovalFlow extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'approval_flow';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='approval_flow';
    protected $fillable = ['order','role_id','competency', 'extension', 'filter_cost_center', 'group_approval_flow_id'];
    protected $hidden = ['role_id'];
    //public $timestamps = false;

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id')->withTrashed();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use App\Models\Role;

class GroupApprovalFlow extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'group_approval_flow';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='group_approval_flow';
    protected $fillable = ['title', 'description'];

    public function approval_flow()
    {
        return $this->hasMany(ApprovalFlow::class, 'group_approval_flow_id', 'id')->with('role');
    }
}

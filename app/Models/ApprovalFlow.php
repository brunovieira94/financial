<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class ApprovalFlow extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['order','role_id'];
    protected static $logName = 'approval_flow';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
    }

    use SoftDeletes;
    protected $table='approval_flow';
    protected $fillable = ['order','role_id'];
    //public $timestamps = false;
}

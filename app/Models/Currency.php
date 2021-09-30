<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Currency extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['title', 'initials'];
    protected static $logName = 'currency';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
    }

    // Model attributes
    use SoftDeletes;
    protected $table='currency';
    protected $fillable = ['title', 'initials'];
}

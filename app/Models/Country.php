<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Country extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['title'];
    protected static $logName = 'country';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    // Model attributes
    use SoftDeletes;
    protected $table='countries';
    protected $fillable = ['title'];
}

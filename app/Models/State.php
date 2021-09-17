<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\City;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class State extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['title', 'country'];
    protected static $logName = 'states';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    use softDeletes;
    protected $fillable = ['title', 'country'];
    protected $table='states';
    protected $appends = ['linked_cities'];

    public function getLinkedCitiesAttribute()
    {
        return $this->hasMany(City::class, 'states_id', 'id')->count();
    }
}

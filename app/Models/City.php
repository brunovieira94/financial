<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\State;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class City extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['title', 'states_id'];
    protected static $logName = 'cities';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    use SoftDeletes;
    protected $table='cities';
    protected $fillable = ['title', 'states_id'];
    protected $hidden = ['states_id'];

    public function state(){
        return $this->belongsTo(State::class, 'states_id', 'id');
    }
}

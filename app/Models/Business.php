<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\LogOptions;

class Business extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['company_id', 'name'];
    protected static $logName = 'business';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
    }

    use SoftDeletes;
    protected $table='business';
    protected $hidden = ['pivot'];
    protected $fillable = ['company_id', 'name'];

    public function costUser()
    {
        return $this->hasMany(BusinessHasCostCenters::class, 'business_id', 'id')->with(['user', 'costCenter']);
    }

}



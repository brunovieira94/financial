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
    protected static $logAttributes = ['company', 'cost_user' , '*'];
    protected static $logName = 'business';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='business';
    protected $hidden = ['pivot', 'company_id'];
    protected $fillable = ['company_id', 'name'];

    public function cost_user()
    {
        return $this->hasMany(BusinessHasCostCenters::class, 'business_id', 'id')->with(['user', 'costCenter']);
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id')->with(['bank_account','managers']);
    }

}



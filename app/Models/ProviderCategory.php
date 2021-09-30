<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class ProviderCategory extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['title','payment_before_weekends'];
    protected static $logName = 'provider_categories';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
    }

    use SoftDeletes;
    protected $fillable = ['title','payment_before_weekends'];
    protected $table='provider_categories';
}

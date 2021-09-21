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
    protected static $logAttributes = ['title'];
    protected static $logName = 'provider_categories';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    use SoftDeletes;
    protected $fillable = ['title'];
    protected $table='provider_categories';
}

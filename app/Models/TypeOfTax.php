<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\LogOptions;

class TypeOfTax extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'type_of_tax';
    protected $fillable = ['title'];
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user() ?? '';
        $activity->causer_id = $user->id ?? '';
    }
    use SoftDeletes;
    protected $table='type_of_tax';

}

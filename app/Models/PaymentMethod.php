<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class PaymentMethod extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['title'];
    protected static $logName = 'payment_method';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    use SoftDeletes;
    protected $table='payment_method';
    protected $fillable = ['title'];
}

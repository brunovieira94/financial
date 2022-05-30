<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Billing extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'billing';
    public function tapActivity(Activity $activity)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    // Model attributes
    use SoftDeletes;
    protected $table = 'billing';
    protected $fillable = ['reserve', 'partner_value', 'pay_date', 'boleto_value', 'boleto_code', 'recipient_name', 'remark', 'oracle_protocol', 'cnpj'];
}

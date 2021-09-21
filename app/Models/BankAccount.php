<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class BankAccount extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['agency_number', 'agency_check_number', 'account_number', 'account_check_number', 'bank_id'];
    protected static $logName = 'bank_accounts';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    use SoftDeletes;
    protected $hidden = ['pivot'];
    protected $table='bank_accounts';
    protected $fillable = ['agency_number', 'agency_check_number', 'account_number', 'account_check_number', 'bank_id'];
}

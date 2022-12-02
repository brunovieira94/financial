<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Service extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['chart_of_account', '*'];
    protected static $logName = 'services';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table = 'services';
    protected $fillable = ['title', 'description', 'chart_of_accounts_id', 'service_code'];
    protected $hidden = ['chart_of_accounts_id'];

    public function chart_of_account()
    {
        return $this->hasOne(ChartOfAccounts::class, 'id', 'chart_of_accounts_id');
    }
}

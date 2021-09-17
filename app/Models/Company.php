<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Company extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['company_name', 'trade_name', 'cnpj'];
    protected static $logName = 'companies';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    use SoftDeletes;
    protected $table='companies';
    protected $fillable = ['company_name', 'trade_name', 'cnpj'];

    public function bankAccount()
    {
        return $this->belongsToMany(BankAccount::class, 'company_has_bank_accounts', 'company_id', 'bank_account_id');
    }
}

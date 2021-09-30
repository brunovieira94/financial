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
    protected static $logAttributes = ['company_name', 'trade_name', 'cnpj', 'cep', 'cities_id', 'address', 'number', 'complement', 'district', 'managers'];
    protected static $logName = 'companies';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
    }

    use SoftDeletes;
    protected $table='companies';
    protected $fillable = ['company_name', 'trade_name', 'cnpj', 'cep', 'cities_id', 'address', 'number', 'complement', 'district'];

    public function bankAccount()
    {
        return $this->belongsToMany(BankAccount::class, 'company_has_bank_accounts', 'company_id', 'bank_account_id');
    }

    public function managers()
    {
        return $this->belongsToMany(User::class, 'company_has_managers', 'company_id', 'manager');
    }
}

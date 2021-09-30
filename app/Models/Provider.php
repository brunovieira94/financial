<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Provider extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['company_name', 'trade_name', 'cpnj', 'responsible', 'provider_categories_id', 'cost_center_id', 'cep', 'cities_id', 'address', 'number', 'complement', 'district', 'phones', 'email', 'user_id', 'responsible_phone', 'responsible_email', 'state_subscription', 'chart_of_accounts_id'];
    protected static $logName = 'providers';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
    }

    use SoftDeletes;
    protected $table='providers';
    protected $casts = [
        'phones' => 'array',
    ];
    protected $fillable = ['company_name', 'trade_name', 'cpnj', 'responsible', 'provider_categories_id', 'cost_center_id', 'cep', 'cities_id', 'address', 'number', 'complement', 'district', 'phones', 'email', 'user_id', 'responsible_phone', 'responsible_email', 'state_subscription', 'chart_of_accounts_id'];

    public function bankAccount()
    {
        return $this->belongsToMany(BankAccount::class, 'provider_has_bank_accounts', 'provider_id', 'bank_account_id');
    }
}

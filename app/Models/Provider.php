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
    protected static $logAttributes = ['*'];
    protected static $logName = 'providers';
    protected $hidden = ['provider_categories_id', 'user_id', 'chart_of_accounts_id', 'cost_center_id', 'cities_id'];
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
    protected $fillable = ['company_name', 'trade_name', 'alias', 'cnpj', 'responsible', 'provider_categories_id', 'cost_center_id', 'cep', 'cities_id', 'address', 'number', 'complement', 'district', 'phones', 'email', 'user_id', 'responsible_phone', 'responsible_email', 'state_subscription', 'chart_of_accounts_id', 'cpf', 'rg', 'full_name', 'birth_date', 'provider_type'];

    public function bankAccount()
    {
        return $this->belongsToMany(BankAccount::class, 'provider_has_bank_accounts', 'provider_id', 'bank_account_id')->with('bank');
    }

    public function providerCategory()
    {
        return $this->hasOne(ProviderCategory::class, 'id', 'provider_categories_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function chartOfAccount()
    {
        return $this->hasOne(ChartOfAccounts::class, 'id', 'chart_of_accounts_id');
    }

    public function costCenter()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id');
    }

    public function city()
    {
        return $this->hasOne(City::class, 'id', 'cities_id');
    }
}

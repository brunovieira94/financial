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
    protected static $logAttributes = ['bank_account', 'provider_category', 'user', 'chart_of_account', 'cost_center', 'city', '*'];
    protected static $logName = 'providers';
    protected $hidden = ['provider_categories_id', 'user_id', 'chart_of_accounts_id', 'cost_center_id', 'cities_id'];
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='providers';
    protected $casts = [
        'phones' => 'array',
    ];
    protected $fillable = ['allows_registration_without_purchase_order', 'company_name', 'trade_name', 'alias', 'cnpj', 'responsible', 'provider_categories_id', 'cost_center_id', 'cep', 'cities_id', 'address', 'number', 'complement', 'district', 'phones', 'email', 'user_id', 'responsible_phone', 'responsible_email', 'state_subscription', 'chart_of_accounts_id', 'cpf', 'rg', 'full_name', 'birth_date', 'provider_type', 'city_subscription', 'accept_billet_payment', 'credit_card_payment', 'international'];

    public function bank_account()
    {
        return $this->belongsToMany(BankAccount::class, 'provider_has_bank_accounts', 'provider_id', 'bank_account_id')->with(['bank', 'bank_account_default']);
    }

    public function provider_category()
    {
        return $this->hasOne(ProviderCategory::class, 'id', 'provider_categories_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function chart_of_account()
    {
        return $this->hasOne(ChartOfAccounts::class, 'id', 'chart_of_accounts_id');
    }

    public function cost_center()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id');
    }

    public function city()
    {
        return $this->hasOne(City::class, 'id', 'cities_id')->with('state');
    }
}

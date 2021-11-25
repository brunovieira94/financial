<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Product extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['chart_of_account', 'measurement_unit', '*'];
    protected static $logName = 'products';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='products';
    protected $fillable = ['title', 'measurement_units_id', 'chart_of_accounts_id', 'description'];
    protected $hidden = ['chart_of_accounts_id', 'measurement_units_id'];

    public function chart_of_account()
    {
        return $this->hasOne(ChartOfAccounts::class, 'id', 'chart_of_accounts_id');
    }

    public function measurement_unit()
    {
        return $this->hasOne(MeasurementUnit::class, 'id', 'measurement_units_id');
    }

    public function attributes()
    {
        return $this->belongsToMany(AttributeType::class, 'product_has_attributes', 'product_id', 'attribute_id')->withPivot('value')->as('value');
    }
}

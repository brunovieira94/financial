<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Country extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'country';
    public function tapActivity(Activity $activity)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    // Model attributes
    use SoftDeletes;
    protected $table = 'countries';
    protected $fillable = ['title', 'country_bank_code'];

    public function state()
    {
        return $this->hasMany(State::class, 'country_id', 'id');
    }

    //delete relationship
    public static function boot()
    {
        parent::boot();
        self::deleting(function ($country) {
            $country->state->each(function ($state) {
                $state->cities()->delete();
            });
            $country->state()->delete();
        });
    }
}

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
    protected static $logAttributes = ['user','cangooroo','*'];
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
    protected $fillable = ['cangooroo_booking_id', 'reserve','supplier_value', 'pay_date', 'boleto_value', 'boleto_code', 'recipient_name', 'remark', 'oracle_protocol', 'user_id', 'payment_status', 'status_123', 'cnpj'];
    protected $hidden = ['user_id', 'cangooroo_booking_id'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function cangooroo()
    {
        return $this->hasOne(Cangooroo::class, 'booking_id', 'cangooroo_booking_id')->with('hotel');
    }

    public static function boot() {
        parent::boot();
        self::deleting(function($billing) {
            $billing->cangooroo()->delete();
        });
    }
}

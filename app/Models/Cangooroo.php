<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Cangooroo extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'billing';
    public function tapActivity(Activity $activity)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    // Model attributes
    use SoftDeletes;
    protected $table = 'cangooroo';
    protected $fillable = [
        'booking_id',
        'guests',
        'service_id',
        'supplier_reservation_code',
        'status',
        'reservation_date',
        'check_in',
        'check_out',
        'number_of_nights',
        'supplier_hotel_id',
        'hotel_id',
        'hotel_name',
        'city_name',
        'agency_name',
        'creation_user',
        'selling_price',
        '123_id',
        'supplier_name',
        'cancellation_policies_start_date',
        'cancellation_policies_value',
        'cancellation_date',
        'last_update',
        'provider_name',
        'is_vcn',
    ];

    public function hotel()
    {
        return $this->hasOne(Hotel::class, 'id_hotel_cangooroo', 'hotel_id')->with('bank_account');
    }
}

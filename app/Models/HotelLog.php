<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;


class HotelLog extends Model
{
    protected $table = 'hotel_log';
    protected $fillable = ['type', 'motive', 'description', 'stage', 'user_id', 'user_name', 'user_role', 'hotel_id', 'created_at'];
    protected $hidden = ['hotel_id'];

    public function hotel()
    {
        return $this->hasOne(Hotel::class, 'id', 'hotel_id')->withTrashed();
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed();
    }
}

<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;


class BillingLog extends Model
{
    protected $table = 'billing_log';
    protected $fillable = ['type', 'motive', 'description', 'stage', 'user_id', 'user_name', 'user_role', 'billing_id', 'created_at'];
    protected $hidden = ['billing_id'];

    public function billing()
    {
        return $this->hasOne(Billing::class, 'id', 'billing_id')->withTrashed();
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed();
    }
}

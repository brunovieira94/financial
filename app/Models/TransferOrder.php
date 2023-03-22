<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class TransferOrder extends Model
{
    use HasFactory;

    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'transfer_orders';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user() ?? '';
        $activity->causer_id = $user->id ?? '';
    }

    protected $table = 'transfer_orders';
    protected $casts = [
        'users_ids' => 'array',
    ];
    protected $fillable = ['purchase_order_id', 'order', 'flag', 'users_ids', 'approve_count'];
    protected $hidden = ['id', 'created_at', 'updated_at'];
    protected $appends = [];
}

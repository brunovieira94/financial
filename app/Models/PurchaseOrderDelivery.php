<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class PurchaseOrderDelivery extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['services', 'products', '*'];
    protected static $logName = 'purchase_order_delivery';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    //use SoftDeletes;
    protected $table = 'purchase_order_deliverys';
    protected $fillable = ['payment_request_id', 'purchase_order_id', 'product_id', 'service_id', 'delivery_quantity', 'status', 'quantity'];
    protected $hidden = ['payment_request_id', 'purchase_order_id', 'product_id', 'service_id', 'created_at', 'updated_at', 'deleted_at'];
}

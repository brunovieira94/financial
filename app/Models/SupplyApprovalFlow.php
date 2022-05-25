<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;


class SupplyApprovalFlow extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['supply_approval_flow', '*'];
    protected static $logName = 'supply_approval_flows';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $user->role = Role::findOrFail($user->role_id);
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    protected $table='supply_approval_flows';
    protected $fillable = ['id_purchase_order','order', 'status', 'reason'];
    public $timestamps = false;
    protected $hidden = ['id_purchase_order'];

    public function purchase_order()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'id_purchase_order')->with(['user','approval','cost_centers', 'attachments', 'services', 'products', 'company', 'currency', 'provider', 'purchase_requests']);
    }

    public function approval_flow()
    {
        return $this->hasOne(ApprovalFlowSupply::class, 'order', 'order')->with('role')->latest();
    }
}

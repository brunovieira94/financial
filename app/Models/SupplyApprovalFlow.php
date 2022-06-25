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

    protected $table = 'supply_approval_flows';
    protected $fillable = ['id_purchase_order', 'order', 'status', 'reason'];
    public $timestamps = false;
    //protected $hidden = ['id_purchase_order'];
    protected $appends = ['approver_stage'];

    public function purchase_order()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'id_purchase_order')->with(['user', 'approval', 'cost_centers', 'attachments', 'services', 'products', 'company', 'currency', 'provider', 'purchase_requests']);
    }

    public function approval_flow()
    {
        return $this->hasOne(ApprovalFlowSupply::class, 'order', 'order')->with('role')->latest();
    }

    public function getApproverStageAttribute()
    {
        $approverStage = [];
        if (SupplyApprovalFlow::where('id_purchase_order', $this->id)->exists()) {
            $approvalId = SupplyApprovalFlow::where('id_purchase_order', $this->id)->firstOrFail();
            $roles = ApprovalFlowSupply::where('order', $approvalId->order)->with('role')->get();
            $costCenters = PurchaseOrderHasCostCenters::where('purchase_order_id', $this->id)->get();
            $costCenterId = null;
            $maxPercentage = 0;
            $constCenterEqual = false;
            foreach ($costCenters as $costCenter) {
                if ($costCenter->percentage > $maxPercentage) {
                    $costCenterId = $costCenter->cost_center_id;
                    $maxPercentage = $costCenter->percentage;
                } else if ($costCenter->percentage == $maxPercentage) {
                    $constCenterEqual = true;
                    $maxPercentage = $costCenter->percentage;
                }
            }

            foreach ($roles as $role) {
                if ($role->role->id != 1) {
                    $checkUser = User::where('role_id', $role->role->id)->with('cost_center')->orderby('name')->get();
                    $names = [];
                    foreach ($checkUser as $user) {
                        if ($constCenterEqual == false) {
                            foreach ($user->cost_center as $userCostCenter) {
                                if ($userCostCenter->id == $costCenterId) {
                                    $names[] = $user->name;
                                }
                            }
                        } else {
                            $names[] = $user->name;
                        }
                    }
                    $approverStage[] = [
                        'title' => $role->role->title,
                        'name' => count($names) > 0 ? $names[0] : '',
                        'names' => $names,
                    ];
                }
            }
            return $approverStage;
        } else {
            return $approverStage;
        }
    }
}

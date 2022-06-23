<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class ApprovalFlowSupply extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'approval_flow_supply';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table = 'approval_flow_supply';
    protected $fillable = ['order', 'role_id'];
    protected $hidden = ['role_id'];
    //public $timestamps = false;
    protected $appends = ['approver_stage'];

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id')->withTrashed();
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

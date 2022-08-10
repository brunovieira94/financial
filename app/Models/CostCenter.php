<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class CostCenter extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'cost_center';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='cost_center';
    protected $fillable = ['title','parent','code', 'group_approval_flow_id', 'group_approval_flow_supply_id'];
    protected $hidden = ['pivot'];


    protected $appends = ['linked_costCenters'];

    public function group_approval_flow()
    {
        return $this->hasOne(GroupApprovalFlow::class, 'id', 'group_approval_flow_id');
    }

    public function getLinkedCostCentersAttribute()
    {
        return $this->hasMany(CostCenter::class, 'parent', 'id')->count();
    }

    public function parent() {
        return $this->belongsTo(self::class, 'parent');
    }

    public function children() {
        return $this->hasMany(self::class, 'parent');
    }

    public static function nestable($costCenters) {
       foreach ($costCenters as $costCenter) {
           if (!$costCenter->children->isEmpty()) {
               $costCenter->children = self::nestable($costCenter->children);
            }
        }

        return $costCenters;
    }
}

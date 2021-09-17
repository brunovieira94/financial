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
    protected static $logAttributes = ['title','parent'];
    protected static $logName = 'cost_center';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    use SoftDeletes;
    protected $table='cost_center';
    protected $fillable = ['title','parent'];


    protected $appends = ['linked_costCenters', 'linked_chartOfAccounts'];

    public function getLinkedCostCentersAttribute()
    {
        return $this->hasMany(CostCenter::class, 'parent', 'id')->count();
    }

    public function getLinkedChartOfAccountsAttribute()
    {
        return $this->hasMany(ChartOfAccounts::class, 'cost_center_id', 'id')->count();
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

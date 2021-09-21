<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends Model
{
    use SoftDeletes;
    protected $table='cost_center';
    protected $fillable = ['title','parent'];
    protected $hidden = ['pivot'];


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

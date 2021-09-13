<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccounts extends Model
{
    use SoftDeletes;
    protected $table='chart_of_accounts';
    protected $fillable = ['title', 'parent', 'cost_center_id'];

    protected $appends = ['linked_chartOfAccounts'];

    public function getLinkedChartOfAccountsAttribute()
    {
        return $this->hasMany(ChartOfAccounts::class, 'parent', 'id')->count();
    }

    public function parent() {
        return $this->belongsTo(self::class, 'parent');
    }

    public function children() {
        return $this->hasMany(self::class, 'parent');
    }

    public static function nestable($chartOfAccounts) {
       foreach ($chartOfAccounts as $chart) {
           if (!$chart->children->isEmpty()) {
               $chart->children = self::nestable($chart->children);
            }
        }

        return $chartOfAccounts;
    }
}

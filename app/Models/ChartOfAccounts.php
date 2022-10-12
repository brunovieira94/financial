<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class ChartOfAccounts extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'chart_of_accounts';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='chart_of_accounts';
    protected $fillable = ['active', 'title', 'parent', 'code', 'group', 'managerial_code', 'managerial_title', 'group_title', 'group_code', 'referential_title', 'referential_code'];

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

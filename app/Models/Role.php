<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Role extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['title','modules'];
    protected static $logName = 'role';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    use SoftDeletes;
    protected $fillable = ['title'];
    protected $table='role';

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'role_has_modules', 'role_id', 'module_id')->withPivot('create', 'read', 'update', 'delete', 'import', 'export')->as('permissions');
    }
}

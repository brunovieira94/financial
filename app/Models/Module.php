<?php

namespace App\Models;

use App\Scopes\FilterActive;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;


class Module extends Model
{

    use SoftDeletes;
    protected static $logAttributes = ['*'];
    protected $table='module';
    protected $fillable = ['title', 'route', 'parent', 'active'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_has_modules', 'module_id', 'role_id')->withPivot('create', 'read', 'update', 'delete', 'import', 'export')->as('permissions');
    }

    protected $appends = ['linked_modules'];

    public function getLinkedModulesAttribute()
    {
        return $this->hasMany(Module::class, 'parent', 'id')->count();
    }

    public function parent() {
        return $this->belongsTo(self::class, 'parent');
    }

    public function children() {
        return $this->hasMany(self::class, 'parent');
    }

    public static function nestable($modules) {
       foreach ($modules as $module) {
           if (!$module->children->isEmpty()) {
               $module->children = self::nestable($module->children);
            }
        }

        return $modules;
    }

    protected static function booted()
    {
        static::addGlobalScope(new FilterActive);
    }
}

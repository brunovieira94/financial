<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;


class Module extends Model
{

    use SoftDeletes;
    protected $fillable = ['title','parent','route'];
    protected $table='module';

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
}

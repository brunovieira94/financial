<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Module extends Model
{
    use SoftDeletes;
    protected $fillable = ['title','parent'];
    protected $table='module';

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_has_modules', 'module_id', 'role_id')->withPivot('create', 'read', 'update', 'delete', 'import', 'export')->as('permissions');
    }
}

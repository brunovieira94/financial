<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleHasModule extends Model
{
    protected $table='role_has_modules';

    protected $fillable = ['title', 'role_id', 'module_id', 'create', 'read', 'update', 'delete', 'delete','export'];

    public function module()
    {
        return $this->hasOne(Module::class, 'id', 'module_id');
    }

}

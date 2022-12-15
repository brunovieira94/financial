<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationCatalogHasRoles extends Model
{
    //Logs

    //use SoftDeletes;
    public $timestamps = false;
    protected $table = 'notification_catalog_has_roles';
    protected $fillable = ['notification_catalog_id', 'role_id'];

    public function user()
    {
        return $this->hasMany(User::class, 'role_id', 'role_id')->with('cost_center');
    }

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }
}

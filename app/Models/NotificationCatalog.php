<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationCatalog extends Model
{
    //Logs

    use SoftDeletes;
    public $timestamps = false;
    protected $table = 'notification_catalogs';
    protected $fillable = ['title', 'type', 'active', 'schedule'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'notification_catalog_has_roles', 'notification_catalog_id', 'role_id')->select('role.id', 'role.title');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_catalog_has_users', 'notification_catalog_id', 'user_id')->select('users.id', 'users.name', 'users.email', 'users.phone');
    }
}

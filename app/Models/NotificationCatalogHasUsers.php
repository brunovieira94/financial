<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationCatalogHasUsers extends Model
{
    //Logs

    //use SoftDeletes;
    public $timestamps = false;
    protected $table = 'notification_catalog_has_users';
    protected $fillable = ['notification_catalog_id', 'user_id'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}

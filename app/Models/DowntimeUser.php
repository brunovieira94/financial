<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class DowntimeUser extends Model
{
    protected $table = 'downtime_user_system';
    protected $fillable = ['user_id', 'updated_at'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}

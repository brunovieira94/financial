<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class AdditionalUser extends Model
{
    protected $table = 'additional_users';
    public $timestamps = false;
    protected $fillable = ['user_id', 'user_additional_id'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function additional_user()
    {
        return $this->hasOne(User::class, 'id', 'user_additional_id');
    }
}

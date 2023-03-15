<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserHasSavedFilter extends Model
{
    protected $table = 'user_has_saved_filter';
    public $timestamps = false;
    protected $fillable = ['user_id', 'name', 'type', 'value'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use SoftDeletes;
    protected $table='cities';
    protected $fillable = ['title', 'states_id'];
}

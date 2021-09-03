<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use softDeletes;
    protected $fillable = ['title', 'country'];
    protected $table='states';
}

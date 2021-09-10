<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\City;

class State extends Model
{
    use softDeletes;
    protected $fillable = ['title', 'country'];
    protected $table='states';
    protected $appends = ['linked_cities'];

    public function getLinkedCitiesAttribute()
    {
        return $this->hasMany(City::class, 'states_id', 'id')->count();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class IntegrationClient extends Model
{
    use SoftDeletes;

    protected $fillable = ['enabled', 'client_id', 'client_secret'];
    protected $hidden = ['client_secret'];

    protected $table = 'integration_clients';
}

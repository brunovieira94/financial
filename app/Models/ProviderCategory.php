<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderCategory extends Model
{
    use softDeletes;
    protected $fillable = ['title'];
    protected $table='provider_categories';
}

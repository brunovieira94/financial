<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsHasAttributes extends Model
{
    protected $table='product_has_attributes';
    public $timestamps = false;
    protected $fillable = ['product_id', 'attribute_id', 'value'];
}

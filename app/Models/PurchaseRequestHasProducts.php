<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestHasProducts extends Model
{
    protected $table='purchase_request_has_products';
    public $timestamps = false;
    protected $fillable = ['purchase_request_id', 'product_id', 'quantity'];
    protected $hidden = ['purchase_request_id', 'product_id'];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}

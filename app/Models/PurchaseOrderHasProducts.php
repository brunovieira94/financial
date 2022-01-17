<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHasProducts extends Model
{
    protected $table='purchase_order_has_products';
    public $timestamps = false;
    protected $fillable = ['purchase_order_id', 'product_id', 'unitary_value', 'quantity', 'percentage_discount', 'money_discount', 'unique_discount'];
    protected $hidden = ['purchase_order_id', 'product_id'];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}

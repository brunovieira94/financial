<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderQuotationHasProducts extends Model
{
    protected $table = 'provider_quotation_has_products';
    public $timestamps = false;
    protected $fillable = ['provider_quotation_item_id', 'product_id', 'quantity', 'quantity_request', 'unit_price', 'total_without_discount', 'discount', 'total_discount', 'observations'];
    protected $hidden = ['provider_quotation_item_id', 'product_id'];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderQuotationItems extends Model
{
    protected $table = 'provider_quotation_items';
    public $timestamps = false;
    protected $casts = [
        'selected_services' => 'array',
        'selected_products' => 'array',
    ];
    protected $fillable = ['provider_quotation_id', 'provider_id', 'selected_products', 'selected_services', 'block_purchase_order'];
    protected $hidden = ['provider_quotation_id', 'provider_id'];

    public function products()
    {
        return $this->hasMany(ProviderQuotationHasProducts::class, 'provider_quotation_item_id', 'id')->with('product');
    }

    public function services()
    {
        return $this->hasMany(ProviderQuotationHasServices::class, 'provider_quotation_item_id', 'id')->with(['service']);
    }

    public function provider()
    {
        return $this->hasOne(Provider::class, 'id', 'provider_id');
    }
}

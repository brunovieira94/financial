<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderQuotationHasServices extends Model
{
    protected $table = 'provider_quotation_has_services';
    public $timestamps = false;
    protected $fillable = ['provider_quotation_item_id', 'service_id', 'contract_duration', 'quantity', 'unit_price', 'total_without_discount', 'discount', 'total_discount', 'observations'];
    protected $hidden = ['provider_quotation_item_id', 'service_id'];

    public function service()
    {
        return $this->hasOne(Service::class, 'id', 'service_id');
    }
}

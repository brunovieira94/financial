<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderQuotationHasCostCenters extends Model
{
    protected $table = 'provider_quotation_has_cost_centers';
    public $timestamps = false;
    protected $fillable = ['provider_quotation_id', 'cost_center_id', 'percentage'];
    protected $hidden = ['provider_quotation_id', 'cost_center_id'];

    public function cost_center()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id')->select(['id', 'title']);
    }
}

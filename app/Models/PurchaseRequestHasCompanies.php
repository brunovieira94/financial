<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestHasCompanies extends Model
{
    protected $table='purchase_request_has_companies';
    public $timestamps = false;
    protected $fillable = ['purchase_request_id', 'company_id'];
    protected $hidden = ['purchase_request_id', 'company_id'];

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderServicesHasInstallments extends Model
{
    protected $table='purchase_order_services_has_installments';
    public $timestamps = false;
    protected $fillable = ['po_services_id', 'parcel_number', 'portion_amount', 'due_date', 'note', 'percentage_discount', 'money_discount'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHasInstallments extends Model
{
    protected $table='purchase_order_has_installments';
    public $timestamps = false;
    protected $fillable = ['payment_request_id', 'purchase_order_id', 'parcel_number', 'portion_amount', 'due_date', 'note', 'percentage_discount', 'money_discount', 'invoice_received', 'invoice_paid'];
}

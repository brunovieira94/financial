<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHasInstallments extends Model
{
    protected $table='purchase_order_has_installments';
    public $timestamps = false;
    protected $fillable = ['amount_paid', 'payment_request_id', 'purchase_order_id', 'parcel_number', 'portion_amount', 'due_date', 'note', 'percentage_discount', 'money_discount', 'invoice_received', 'invoice_paid'];

    //public function getAmountReceivedAttribute()
    //{
    //    if(PaymentRequestHasPurchaseOrderInstallments::where('purchase_order_has_installments_id', $this->id)->exists()){
    //        return PaymentRequestHasPurchaseOrderInstallments::where('purchase_order_has_installments_id', $this->id)->sum('amount_received');
    //    }else{
    //        return 0;
    //    }
    //}

    public function purchase_order()
    {
        return $this->hasOne(PurchaseOrder::class, 'id', 'purchase_order_id');
    }
}



<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillToPayHasInstallments extends Model
{
    protected $table='bill_to_pay_has_installments';
    public $timestamps = false;
    protected $fillable = ['parcel_number', 'id_bill_to_pay', 'due_date', 'note', 'portion_amount', 'status', 'status', 'amount_received'];
}

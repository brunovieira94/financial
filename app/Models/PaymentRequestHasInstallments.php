<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestHasInstallments extends Model
{
    protected $table='payment_requests_installments';
    public $timestamps = false;
    protected $fillable = ['initial_value', 'discount', 'fees', 'extension_date', 'competence_date', 'parcel_number', 'payment_request_id', 'due_date', 'note', 'portion_amount', 'status', 'status', 'amount_received'];
}

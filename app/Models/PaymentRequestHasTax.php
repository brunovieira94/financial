<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestHasTax extends Model
{
    protected $table='payment_requests_has_tax';
    public $timestamps = false;
    protected $fillable = ['payment_request_id', 'type_of_tax_id', 'tax_amount'];
    protected $hidden = ['type_of_tax_id', 'payment_request_id'];

    public function typeOfTax()
    {
        return $this->hasOne(TypeOfTax::class, 'id', 'type_of_tax_id');
    }
}

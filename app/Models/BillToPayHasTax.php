<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillToPayHasTax extends Model
{
    protected $table='bill_to_pay_has_tax';
    public $timestamps = false;
    protected $fillable = ['id_bill_to_pay', 'id_type_of_tax', 'tax_amount'];
    protected $hidden = ['id_type_of_tax', 'id_bill_to_pay'];

    public function typeOfTax()
    {
        return $this->hasOne(TypeOfTax::class, 'id', 'id_type_of_tax');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CnabBilling extends Model
{
    protected $table='cnab_billings';
    public $timestamps = false;
    protected $fillable = ['cnab_generated_id', 'billing_payment_id'];
    protected $hidden = ['pivot', 'cnab_generated_id', 'id', 'billing_payment_id'];


    public function payment_request()
    {
        return $this->hasOne(BillingPayment::class, 'id', 'billing_payment_id')->with(['billings']);
    }

    public function cnab_generated()
    {
        return $this->hasOne(CnabGenerated::class, 'id', 'cnab_generated_id');
    }
}

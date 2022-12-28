<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherPaymentHasExchangeRates extends Model
{
    protected $table = 'other_payment_has_exchange_rates';
    protected $fillable = ['currency_id', 'exchange_rate', 'other_payment_id'];
    public $timestamps = false;

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function other_payment()
    {
        return $this->hasOne(OtherPayment::class, 'id', 'other_payment_id');
    }
}

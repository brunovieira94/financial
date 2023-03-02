<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherPayment extends Model
{
    protected $table = 'other_payments';
    protected $fillable = ['group_form_payment_id', 'note', 'bank_account_company_id', 'payment_date', 'user_id'];

    public function group_form_payment()
    {
        return $this->hasOne(GroupFormPayment::class, 'id', 'group_form_payment_id');
    }

    public function bank_account_company()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_company_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function exchange_rates()
    {
        return $this->hasMany(OtherPaymentHasExchangeRates::class, 'other_payment_id', 'id');
    }

    public function attachments()
    {
        return $this->hasMany(OtherPaymentHasAttachments::class, 'other_payment_id', 'id');
    }

    public function installments()
    {
        return $this->belongsToMany(PaymentRequestHasInstallments::class, 'payment_request_installments_have_other_payments', 'other_payment_id', 'payment_request_installment_id');
    }
}

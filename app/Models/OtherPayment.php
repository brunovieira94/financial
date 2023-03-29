<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class OtherPayment extends Model
{
    protected $table = 'other_payments';
    protected $fillable = ['group_form_payment_id', 'note', 'bank_account_company_id', 'payment_date', 'user_id', 'system_payment_method', 'import_file'];
    protected $appends = ['import_file_link'];

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

    public function getImportFileLinkAttribute()
    {
        if (isset($this->attributes['import_file'])) {
            $importFile = $this->attributes['import_file'];
            return Storage::disk('s3')->temporaryUrl("import-file-payment-request-installment/{$importFile}", now()->addMinutes(30));
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Storage;

class PaymentRequest extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['installments', 'provider', 'bank_account_provider', 'bank_account_company', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', '*'];
    protected static $logName = 'payment_request';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }
    use SoftDeletes;
    protected $table = 'payment_requests';
    protected $hidden = ['provider_id', 'bank_account_provider_id', 'bank_account_company_id', 'bank_account_company_id', 'business_id', 'cost_center_id', 'chart_of_account_id', 'currency_id', 'user_id'];
    protected $appends = ['billet_link', 'invoice_link', 'xml_link'];

    protected $fillable = [
        'provider_id',
        'emission_date',
        'pay_date',
        'bank_account_provider_id',
        'bank_account_company_id',
        'amount',
        'business_id',
        'cost_center_id',
        'chart_of_account_id',
        'currency_id',
        'exchange_rate',
        'frequency_of_installments',
        'invoice_number',
        'net_value',
        'bar_code',
        'invoice_file',
        'billet_file',
        'user_id',
        'xml_file',
    ];

    public function getXmlLinkAttribute()
    {
        if (!is_null($this->attributes['xml_file'])) {
            $XML = $this->attributes['xml_file'];
            return Storage::disk('s3')->temporaryUrl("XML/{$XML}", now()->addMinutes(5));
        }
    }

    public function getBilletLinkAttribute()
    {
        if (!is_null($this->attributes['billet_file'])) {
            $billet = $this->attributes['billet_file'];
            return Storage::disk('s3')->temporaryUrl("billet/{$billet}", now()->addMinutes(5));
        }
    }
    public function getInvoiceLinkAttribute()
    {
        if (!is_null($this->attributes['invoice_file'])) {
            $invoice = $this->attributes['invoice_file'];
            return Storage::disk('s3')->temporaryUrl("invoice/{$invoice}", now()->addMinutes(5));
        }
    }

    public function approval()
    {
        return $this->hasOne(AccountsPayableApprovalFlow::class, 'payment_request_id', 'id')->with('approval_flow');
    }

    public function installments()
    {
        return $this->hasMany(PaymentRequestHasInstallments::class, 'payment_request_id', 'id');
    }

    public function provider()
    {
        return $this->hasOne(Provider::class, 'id', 'provider_id')->with(['city']);
    }

    public function bank_account_provider()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_provider_id');
    }

    public function bank_account_company()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_company_id');
    }

    public function business()
    {
        return $this->hasOne(Business::class, 'id', 'business_id');
    }

    public function cost_center()
    {
        return $this->hasOne(CostCenter::class, 'id', 'cost_center_id');
    }

    public function chart_of_accounts()
    {
        return $this->hasOne(ChartOfAccounts::class, 'id', 'chart_of_account_id');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function tax()
    {
        return $this->hasMany(PaymentRequestHasTax::class, 'payment_request_id', 'id')->with('typeOfTax');
    }

}

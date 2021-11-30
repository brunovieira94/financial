<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Storage;

class BillToPay extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['installments', 'provider', 'bank_account_provider', 'bank_account_company', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', '*'];
    protected static $logName = 'bill_to_pay';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->user()->id;
    }
    use SoftDeletes;
    protected $table='bills_to_pay';
    protected $hidden = ['id_provider', 'id_bank_account_provider', 'id_bank_account_company', 'id_bank_account_company', 'id_business', 'id_cost_center', 'id_chart_of_account', 'id_currency', 'id_user'];
    protected $appends = ['billet_link', 'invoice_link'];

    protected $fillable = [
                            'id_provider',
                            'emission_date',
                            'emission_date',
                            'emission_date',
                            'pay_date',
                            'id_bank_account_provider',
                            'id_bank_account_company',
                            'amount',
                            'id_business',
                            'id_cost_center',
                            'id_chart_of_account',
                            'id_currency',
                            'exchange_rate',
                            'frequency_of_installments',
                            'invoice_number',
                            'type_of_tax',
                            'tax_amount',
                            'net_value',
                            'bar_code',
                            'invoice_file',
                            'billet_file',
                            'id_user',
                        ];

    public function getBilletLinkAttribute()
    {
        if (!is_null($this->attributes['billet_file'])) {
            $billet = $this->attributes['billet_file'];
            return Storage::disk('s3')->temporaryUrl("billet/{$billet}", now()->addMinutes(5));
        }
    }
    public function getInvoiceLinkAttribute()
    {
        if(!is_null($this->attributes['invoice_file'])){
           $invoice = $this->attributes['invoice_file'];
           return Storage::disk('s3')->temporaryUrl("invoice/{$invoice}", now()->addMinutes(5));
        }
    }

    public function approval()
    {
        return $this->hasOne(AccountsPayableApprovalFlow::class, 'id_bill_to_pay', 'id');
    }

    public function installments()
    {
        return $this->hasMany(BillToPayHasInstallments::class, 'bill_to_pay', 'id');
    }

    public function provider()
    {
        return $this->hasOne(Provider::class, 'id', 'id_provider')->with(['city']);
    }

    public function bank_account_provider()
    {
        return $this->hasOne(BankAccount::class, 'id', 'id_bank_account_provider');
    }

    public function bank_account_company()
    {
        return $this->hasOne(BankAccount::class, 'id', 'id_bank_account_company');
    }

    public function business()
    {
        return $this->hasOne(Business::class, 'id', 'id_business');
    }

    public function cost_center()
    {
        return $this->hasOne(CostCenter::class, 'id', 'id_cost_center');
    }

    public function chart_of_accounts()
    {
        return $this->hasOne(ChartOfAccounts::class, 'id', 'id_chart_of_account');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'id_currency');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'id_user');
    }
    //delete relationship
    public static function boot() {
        parent::boot();
        self::deleting(function($approval) {
            $approval->approval()->delete();
        });
    }
}

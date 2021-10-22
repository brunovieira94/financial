<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class BillToPay extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'bill_tp_pay';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->user()->id;
    }
    use SoftDeletes;
    protected $table='bills_to_pay';
    protected $hidden = ['id_provider', 'id_bank_account_provider', 'id_bank_account_company', 'id_bank_account_company', 'id_business', 'id_cost_center', 'id_chart_of_account', 'id_currency', 'id_user'];

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

    public function installments()
    {
        return $this->hasMany(BillToPayHasInstallments::class, 'bill_to_pay', 'id');
    }

    public function provider()
    {
        return $this->hasOne(Provider::class, 'id', 'id_provider');
    }

    public function bankAccountProvider()
    {
        return $this->hasOne(BankAccount::class, 'id', 'id_bank_account_provider');
    }

    public function bankAccountCompany()
    {
        return $this->hasOne(BankAccount::class, 'id', 'id_bank_account_company');
    }

    public function business()
    {
        return $this->hasOne(Business::class, 'id', 'id_business');
    }

    public function costCenter()
    {
        return $this->hasOne(CostCenter::class, 'id', 'id_cost_center');
    }

    public function chartOfAccounts()
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
}

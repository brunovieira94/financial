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
    protected static $logAttributes = [];
    protected static $logName = 'bill_tp_pay';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->user()->id;
    }
    use SoftDeletes;
    protected $table='bills_to_pay';

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


}

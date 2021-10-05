<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;


class AccountsPayableApprovalFlow extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['id_bill_to_pay', 'order', 'status', 'reason'];
    protected static $logName = 'accounts_payable_approval_flows';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
    }

    protected $table='accounts_payable_approval_flows';
    protected $fillable = ['id_bill_to_pay','order', 'status', 'reason'];
    public $timestamps = false;
    protected $hidden = ['id_bill_to_pay'];

    public function billToPay()
    {
        $class = BillToPay::class;
        $class::$staticMakeVisible = ['id_provider', 'id_bank_account_provider', 'id_bank_account_company', 'id_bank_account_company', 'id_business', 'id_cost_center', 'id_chart_of_account', 'id_currency', 'id_user'];

        return $this->hasOne(BillToPay::class, 'id', 'id_bill_to_pay');
    }
}

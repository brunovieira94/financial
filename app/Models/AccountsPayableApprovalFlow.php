<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;


class AccountsPayableApprovalFlow extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
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
        return $this->hasOne(BillToPay::class, 'id', 'id_bill_to_pay')->with(['provider', 'bankAccountProvider', 'bankAccountCompany', 'business', 'costCenter', 'chartOfAccounts', 'currency', 'user']);
    }
}

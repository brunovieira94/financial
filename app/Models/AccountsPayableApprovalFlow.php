<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;


class AccountsPayableApprovalFlow extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['payment_request', '*'];
    protected static $logName = 'accounts_payable_approval_flows';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    protected $table='accounts_payable_approval_flows';
    protected $fillable = ['reason_to_reject_id', 'payment_request_id', 'order', 'status', 'reason'];
    public $timestamps = false;
    protected $hidden = ['payment_request_id'];

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->with(['approval', 'installments', 'provider', 'bank_account_provider', 'bank_account_company', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user']);
    }

    public function approval_flow()
    {
        return $this->hasOne(ApprovalFlow::class, 'order', 'order')->with('role')->latest();
    }

    public function reason_to_reject()
    {
        return $this->hasOne(ReasonToReject::class, 'id', 'reason_to_reject_id');
    }
}

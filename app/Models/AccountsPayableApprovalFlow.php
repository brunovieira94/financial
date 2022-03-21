<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;


class AccountsPayableApprovalFlow extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['payment_request', 'reason_to_reject', '*'];
    protected static $logName = 'accounts_payable_approval_flows';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $user->role = Role::findOrFail($user->role_id);
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    protected $table='accounts_payable_approval_flows';
    protected $fillable = ['reason_to_reject_id', 'payment_request_id', 'order', 'status', 'reason'];
    public $timestamps = false;
    protected $hidden = ['payment_request_id', 'reason_to_reject_id'];

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->with(['company', 'attachments', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', 'tax', 'group_payment']);
    }

    public function payment_request_trashed()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->with(['company', 'attachments', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', 'tax', 'group_payment'])->withTrashed();
    }

    public function approval_flow()
    {
        return $this->hasOne(ApprovalFlow::class, 'order', 'order')->with('role')->latest();
    }

    public function reason_to_reject()
    {
        return $this->hasOne(ReasonToReject::class, 'id', 'reason_to_reject_id')->withTrashed();
    }
}

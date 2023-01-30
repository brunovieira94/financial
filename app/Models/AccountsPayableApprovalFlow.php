<?php

namespace App\Models;

use Exception;
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

    protected $table = 'accounts_payable_approval_flows';
    protected $fillable = ['group_approval_flow_id', 'action', 'reason_to_reject_id', 'payment_request_id', 'order', 'status', 'reason'];
    public $timestamps = false;
    protected $hidden = ['payment_request_id', 'reason_to_reject_id'];
    protected $appends = ['approver_stage', 'approver_stage_first'];

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->with(['currency_old', 'group_approval_flow', 'purchase_order', 'group_payment', 'company', 'attachments', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', 'tax', 'group_payment']);
    }

    public function payment_request_trashed()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->with(['currency_old', 'group_payment', 'company', 'attachments', 'approval', 'installments', 'provider', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', 'tax', 'group_payment'])->withTrashed();
    }

    public function installment_payment_request()
    {
        return $this->hasMany(PaymentRequestHasInstallments::class, 'payment_request_id', 'payment_request_id')->with(['payment_request']);
    }

    public function approval_flow()
    {
        return $this->hasOne(ApprovalFlow::class, 'order', 'order')->with('role')->latest();
    }

    public function reason_to_reject()
    {
        return $this->hasOne(ReasonToReject::class, 'id', 'reason_to_reject_id')->withTrashed();
    }

    public function getApproverStageAttribute()
    {

        $approverStage = [];
        $roles = ApprovalFlow::where('order', $this->order)->where('group_approval_flow_id', $this->group_approval_flow_id)->with('role')->get();
        $costCenterId = PaymentRequest::where('id', $this->payment_request_id)->withTrashed()->withoutGlobalScopes()->first()->cost_center_id;
        foreach ($roles as $role) {
            if ($role->role->id != 1) {
                $checkUser = User::where('role_id', $role->role->id)->where('status', 0)->with('cost_center')->get();
                $names = [];
                foreach ($checkUser as $user) {
                    foreach ($user->cost_center as $userCostCenter) {
                        if ($userCostCenter->id == $costCenterId) {
                            $names[] = $user->name;
                        }
                    }
                }
                $approverStage[] = [
                    'title' => $role->role->title,
                    'name' => count($names) > 0 ? $names[0] : '',
                    'names' => $names,
                ];
            }
        }
        return $approverStage;
    }

    public function getApproverStageFirstAttribute()
    {
        $statusOrder = [0, 2, 8, 9];
        if (in_array($this->status, $statusOrder)) {
            if (ApprovalFlow::with('role')
                ->where('order', $this->order)
                ->where('group_approval_flow_id', $this->group_approval_flow_id)
                ->orderBy('id', 'ASC')
                ->whereRelation('role', 'deleted_at', '=', null)->exists()
            ) {
                $approvalFlow = ApprovalFlow::with('role')
                    ->where('order', $this->order)
                    ->where('group_approval_flow_id', $this->group_approval_flow_id)
                    ->orderBy('id', 'ASC')
                    ->whereRelation('role', 'deleted_at', '=', null)
                    ->first();
                return [
                    'title' => $approvalFlow->role->title
                ];
            } else {
                return [
                    'title' => ''
                ];
            }
        } else {
            return [
                'title' => ''
            ];
        }
    }
}

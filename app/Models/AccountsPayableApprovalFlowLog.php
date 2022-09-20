<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;


class AccountsPayableApprovalFlowLog extends Model
{
    protected $table = 'accounts_payable_approval_flows_log';
    protected $fillable = ['recipient', 'type', 'motive', 'description', 'stage', 'user_id', 'user_name', 'user_role', 'payment_request_id', 'created_at'];
    protected $hidden = ['payment_request_id'];

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->withTrashed();
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->withTrashed();
    }
}

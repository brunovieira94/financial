<?php

use App\Models\AccountsPayableApprovalFlow;
use App\Models\PaymentRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SolveProblemPaymentRequestGroupApprovalFlow extends Migration
{
    public function up()
    {
        foreach (PaymentRequest::withoutGlobalScopes()->withTrashed()->whereNull('group_approval_flow_id')->get() as $paymentRequest) {
            DB::table('payment_requests')
                ->where('id', $paymentRequest['id'])
                ->update(['group_approval_flow_id' => 1]);
            DB::table('accounts_payable_approval_flows')
                ->where('payment_request_id', $paymentRequest['id'])
                ->update(['group_approval_flow_id' => 1]);
        }
    }
}

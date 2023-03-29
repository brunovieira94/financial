<?php

use App\Models\PaymentRequestClean;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedVerificationApprovalPaymentRequest extends Migration
{
    public function up()
    {
        foreach(PaymentRequestClean::with('cost_center')->withOutGlobalScopes()->doesnthave('approval')->withTrashed()->get() as $paymentRequest){
            DB::table('accounts_payable_approval_flows')->insert([
                [
                    'payment_request_id'     => $paymentRequest->id,
                    'order' => 0,
                    'status' => 0,
                    'reason' => null,
                    'reason_to_reject_id' => null,
                    'action' => null,
                    'group_approval_flow_id' => $paymentRequest->cost_center->group_approval_flow_id == null ? 1 : $paymentRequest->cost_center->group_approval_flow_id,
                ]
            ]);
        }
    }
}

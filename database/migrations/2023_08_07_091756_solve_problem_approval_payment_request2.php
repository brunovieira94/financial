<?php

use App\Models\AccountsPayableApprovalFlow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SolveProblemApprovalPaymentRequest2 extends Migration
{
    public function up()
    {
        foreach (DB::table('payment_requests')->get() as $id) {
            $id = $id->id;
            if (!AccountsPayableApprovalFlow::where('payment_request_id', $id)->exists()) {

                $paymentRequest = DB::table('payment_requests')
                    ->where('id', $id)
                    ->first();

                DB::table('accounts_payable_approval_flows')->where('payment_request_id', $id)
                    ->insert(
                        [
                            'payment_request_id' => $paymentRequest->id,
                            'order' => 1,
                            'status' => 0,
                            'group_approval_flow_id' => $paymentRequest->group_approval_flow_id,
                        ],
                    );
            }
        }
    }
}

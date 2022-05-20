<?php

use App\Models\AccountsPayableApprovalFlow;
use App\Models\PaymentRequest;
use App\Scopes\ProfileCostCenterScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SolveProblemApprovalFlow extends Migration
{
    public function up()
    {
        $paymentRequests = PaymentRequest::withoutGlobalScope(ProfileCostCenterScope::class)
            ->with('approval')
            ->get();

        foreach ($paymentRequests as $paymentRequest) {
            if($paymentRequest->approval == NULL)
            {
                DB::table('accounts_payable_approval_flows')->insert([
                    'payment_request_id' => $paymentRequest->id,
                    'order' => 1,
                    'status' => 0,
                ]);
            }
        }
    }
}

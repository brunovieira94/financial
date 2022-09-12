<?php

use App\Models\AccountsPayableApprovalFlow;
use App\Models\PaymentRequest;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedGroupApprovalAccountPayableApprovalFlow extends Migration
{
    public function up()
    {
        Schema::table('accounts_payable_approval_flows', function (Blueprint $table) {
            $table->foreign('group_approval_flow_id')->references('id')->on('group_approval_flow')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('group_approval_flow_id')->unsigned()->nullable();
        });

        foreach (AccountsPayableApprovalFlow::get() as $accountsPayableApprovalFlow) {
            $paymentRequest = PaymentRequest::withoutGlobalScopes()->withTrashed()->where('id', $accountsPayableApprovalFlow['payment_request_id'])->first();
            AccountsPayableApprovalFlow::where('id', $accountsPayableApprovalFlow['id'])->update(['group_approval_flow_id' => $paymentRequest->group_approval_flow_id]);
        }
    }

    public function down()
    {
        //
    }
}

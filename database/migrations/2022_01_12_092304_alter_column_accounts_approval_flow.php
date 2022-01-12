<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnAccountsApprovalFlow extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts_payable_approval_flows', function (Blueprint $table) {
            $table->renameColumn('id_bill_to_pay', 'payment_request_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts_payable_approval_flows', function (Blueprint $table) {
            $table->renameColumn('payment_request_id', 'id_bill_to_pay');
        });
    }
}

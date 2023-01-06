<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAccountsPayableApprovalFlowDescriptionType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts_payable_approval_flows_log', function (Blueprint $table) {
            $table->mediumText('description')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts_payable_approval_flows_log', function (Blueprint $table) {
            $table->string('description')->change();
        });
    }
}

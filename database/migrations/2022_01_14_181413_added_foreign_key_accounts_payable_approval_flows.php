<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedForeignKeyAccountsPayableApprovalFlows extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts_payable_approval_flows', function (Blueprint $table) {
            $table->integer('reason_to_reject_id')->unsigned()->nullable();
            $table->foreign('reason_to_reject_id')->references('id')->on('reasons_to_reject')->constrained()->onUpdate('cascade')->onDelete('cascade');
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
            $table->dropColumn('reason_to_reject_id');
        });
    }
}

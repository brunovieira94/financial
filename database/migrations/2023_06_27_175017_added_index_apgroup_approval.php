<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedIndexApgroupApproval extends Migration
{
    public function up()
    {
        Schema::table('accounts_payable_approval_flows', function (Blueprint $table) {
            $table->index(['group_approval_flow_id']);
        });
    }

    public function down()
    {
        Schema::table('accounts_payable_approval_flows', function (Blueprint $table) {
            $table->dropIndex(['group_approval_flow_id']);
        });
    }
}

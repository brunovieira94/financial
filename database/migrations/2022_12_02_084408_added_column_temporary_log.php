<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnTemporaryLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts_payable_approval_flows_log', function (Blueprint $table) {
            $table->boolean('temporary')->default(false);
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
            $table->dropColumn('temporary');
        });
    }
}

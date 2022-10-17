<?php

use App\Models\GroupApprovalFlow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnInGroupApprovalFlow extends Migration
{
    public function up()
    {
        Schema::table('group_approval_flow', function (Blueprint $table) {
            $table->boolean('default')->default(false)->nullable();
        });

        DB::statement(
            "UPDATE group_approval_flow
             SET `default` = 1
             WHERE id = 1;"
        );
    }

    public function down()
    {
        Schema::table('group_approval_flow', function (Blueprint $table) {
            $table->dropColumn('default');
        });
    }
}

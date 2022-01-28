<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnApprovalFlow extends Migration
{
    public function up()
    {
        Schema::table('approval_flow', function (Blueprint $table) {
            $table->renameColumn('prorrogation_competency', 'competency');
            $table->boolean('extension')->default(false);
            $table->boolean('filter_cost_center')->default(false);
        });
    }

    public function down()
    {
        Schema::table('approval_flow', function (Blueprint $table) {
            $table->renameColumn('competency', 'prorrogation_competency');
            $table->dropColumn('extension');
            $table->dropColumn('filter_cost_center');
        });
    }
}

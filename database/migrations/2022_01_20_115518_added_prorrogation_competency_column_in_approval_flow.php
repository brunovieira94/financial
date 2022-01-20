<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedProrrogationCompetencyColumnInApprovalFlow extends Migration
{
    public function up()
    {
        Schema::table('approval_flow', function (Blueprint $table) {
            $table->boolean('prorrogation_competency')->default(false);
        });
    }

    public function down()
    {
        Schema::table('approval_flow', function (Blueprint $table) {
            $table->dropColumn('prorrogation_competency');
        });
    }
}

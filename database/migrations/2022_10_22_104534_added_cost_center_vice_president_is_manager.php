<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedCostCenterVicePresidentIsManager extends Migration
{
    public function up()
    {
        Schema::create('cost_center_has_vice_president_manager', function (Blueprint $table) {
            $table->increments('id');
            $table->foreign('cost_center_id', 'cc_cost_center_id_foreign')->references('id')->on('cost_center')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('cost_center_id')->unsigned()->nullable();
            $table->foreign('manager_user_id', 'cc_manager_id_foreign')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('manager_user_id')->unsigned()->nullable();
            $table->foreign('vice_president_user_id', 'cc_vice_president_id_foreign')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('vice_president_user_id')->unsigned()->nullable();
        });
    }

    public function down()
    {
        Schema::table('cost_center_has_vice_president_manager', function (Blueprint $table) {
            $table->dropForeign('cc_cost_center_id_foreign');
            $table->dropColumn('cost_center_id');
            $table->dropForeign('cc_manager_id_foreign');
            $table->dropColumn('manager_user_id');
            $table->dropForeign('cc_vice_president_id_foreign');
            $table->dropColumn('vice_president_user_id');
        });
    }
}

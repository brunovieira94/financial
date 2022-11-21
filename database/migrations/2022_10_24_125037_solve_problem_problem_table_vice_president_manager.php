<?php

use App\Models\Cost_Center_Has_VP_M;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SolveProblemProblemTableVicePresidentManager extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::table('cost_center_has_vice_president_manager', function (Blueprint $table) {
            $table->dropForeign('cc_cost_center_id_foreign');
            $table->dropColumn('cost_center_id');
            $table->dropForeign('cc_manager_id_foreign');
            $table->dropColumn('manager_user_id');
            $table->dropForeign('cc_vice_president_id_foreign');
            $table->dropColumn('vice_president_user_id');
        });

        Schema::dropIfExists('cost_center_has_vice_president_manager');

        Schema::create('cost_center_has_vice_president', function (Blueprint $table) {
            $table->increments('id');
            $table->foreign('cost_center_id', 'cc_id_vp_foreign')->references('id')->on('cost_center')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('cost_center_id')->unsigned()->nullable();
            $table->foreign('vice_president_user_id', 'cc_vp_id_foreign')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('vice_president_user_id')->unsigned()->nullable();
        });

        Schema::create('cost_center_has_manager', function (Blueprint $table) {
            $table->increments('id');
            $table->foreign('cost_center_id', 'cc_id_manager_foreign')->references('id')->on('cost_center')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('cost_center_id')->unsigned()->nullable();
            $table->foreign('manager_user_id', 'cc_mg_id_foreign')->references('id')->on('users')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('manager_user_id')->unsigned()->nullable();
        });
    }

    public function down()
    {
        Schema::table('cost_center_has_vice_president', function (Blueprint $table) {
            $table->dropForeign('cc_id_foreign');
            $table->dropColumn('cost_center_id');
            $table->dropForeign('cc_vp_id_foreign');
            $table->dropColumn('vice_president_user_id');
        });

        Schema::table('cost_center_has_manager', function (Blueprint $table) {
            $table->dropForeign('cc_id_foreign');
            $table->dropColumn('cost_center_id');
            $table->dropForeign('cc_mg_id_foreign');
            $table->dropColumn('manager_user_id');
        });
    }
}

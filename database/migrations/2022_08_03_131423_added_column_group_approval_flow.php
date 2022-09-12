<?php

use App\Models\ApprovalFlow;
use App\Models\CostCenter;
use App\Models\GroupApprovalFlow;
use App\Models\PaymentRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnGroupApprovalFlow extends Migration
{
    public function up()
    {
        Schema::create('group_approval_flow', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('approval_flow', function (Blueprint $table) {
            $table->foreign('group_approval_flow_id')->references('id')->on('group_approval_flow')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('group_approval_flow_id')->unsigned()->nullable();
        });

        Schema::table('cost_center', function (Blueprint $table) {
            $table->foreign('group_approval_flow_id')->references('id')->on('group_approval_flow')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('group_approval_flow_id')->unsigned()->nullable();
            $table->foreign('group_approval_flow_supply_id')->references('id')->on('group_approval_flow')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('group_approval_flow_supply_id')->unsigned()->nullable();
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->foreign('group_approval_flow_id')->references('id')->on('group_approval_flow')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('group_approval_flow_id')->unsigned()->nullable();
        });

        DB::table('group_approval_flow')->insert([
            'title' => 'Fluxo de aprovação',
            'description' => ''
        ]);

        CostCenter::withTrashed()->update(
            [
                'group_approval_flow_id' => 1
            ]
        );

        PaymentRequest::withTrashed()->withoutGlobalScopes()->update(
            [
                'group_approval_flow_id' => 1
            ]
        );

        ApprovalFlow::withTrashed()->update(
            [
                'group_approval_flow_id' => 1
            ]
        );
    }

    public function down()
    {
        Schema::dropIfExists('group_approval_flow');
    }
}

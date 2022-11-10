<?php

use App\Models\LogActivity;
use App\Models\SupplyApprovalFlow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDateApprovedSupplyApprovalFlowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('supply_approval_flows', function (Blueprint $table) {

            foreach (SupplyApprovalFlow::where('status', 1)->whereNull('updated_at')->get() as $supplyAprovalFlow) {
                $createdAt = '';
                $logPurchaseOrder =  LogActivity::where([
                    ['log_name', 'supply_approval_flows'],
                    ['subject_id', $supplyAprovalFlow->id]
                ])->orderBy('created_at', 'asc')->get();

                foreach ($logPurchaseOrder as $log) {
                    if ($log['log_name'] == 'supply_approval_flows') {
                        if ($log['properties']['attributes']['status'] == 1) {
                            $createdAt = $log['created_at'];
                        }
                    }
                }

                SupplyApprovalFlow::where('id', $supplyAprovalFlow->id)->update(['updated_at' => $createdAt]);
            }
        });
    }
}

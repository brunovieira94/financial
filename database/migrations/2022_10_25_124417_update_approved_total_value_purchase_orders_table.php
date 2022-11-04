<?php

use App\Models\PurchaseOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateApprovedTotalValuePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (PurchaseOrder::with(['approval'])->get() as $purchaseOrder) {
            if ($purchaseOrder->approval != NULL) {
                if ($purchaseOrder->approval->status == 1) {
                    PurchaseOrder::where('id', $purchaseOrder->id)->update([
                        'approved_total_value' => $purchaseOrder->negotiated_total_value
                    ]);
                }
            }
        }
    }
}

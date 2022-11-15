<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderHasInstallments;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNegotiatedTotalValuePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            foreach (PurchaseOrder::get() as $purchaseOrder) {
                if ($purchaseOrder->negotiated_total_value == NULL) {
                    $new_negotiated_total_value = 0;
                    foreach (PurchaseOrderHasInstallments::where('purchase_order_id', $purchaseOrder->id)->get() as $new_value) {
                        $new_negotiated_total_value += $new_value['portion_amount'] - $new_value['money_discount'];
                    }

                    PurchaseOrder::where('id', $purchaseOrder->id)->update([
                        'negotiated_total_value' => $new_negotiated_total_value
                    ]);
                }
            }
        });
    }
}

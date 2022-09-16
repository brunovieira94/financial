<?php

use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderHasProducts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedStatusPurchaseOrderDeliveryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_order_deliverys', function (Blueprint $table) {
            $table->integer('status')->after('delivery_quantity');
            $table->integer('quantity')->after('delivery_quantity');
        });

        foreach (PurchaseOrderDelivery::get() as $delivery) {
            $totalQuantity = PurchaseOrderHasProducts::where(
                [
                    'purchase_order_id' => $delivery->purchase_order_id,
                    'product_id' => $delivery->product_id,
                ]
            )->first(['quantity']);

            if ($totalQuantity != null) {
                PurchaseOrderDelivery::where(
                    [
                        'payment_request_id' => $delivery->payment_request_id,
                        'purchase_order_id' => $delivery->purchase_order_id,
                        'product_id' => $delivery->product_id,
                    ]
                )->update(
                    [
                        'quantity' => $totalQuantity->quantity,
                        'status' => ($totalQuantity->quantity - $delivery->delivery_quantity) == 0 ? 2 : 1
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /* Schema::table('purchase_order_deliverys', function (Blueprint $table) {
            //
        }); */
    }
}

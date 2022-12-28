<?php

use App\Models\PaymentRequest;
use App\Models\PaymentRequestHasPurchaseOrders;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderHasProducts;
use App\Models\SupplyApprovalFlow;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNfPurchaseOrderDeliveryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (PaymentRequest::where([
            'payment_type' => 0,
            'deleted_at' => null
        ])->get() as $paymentRequest) {
            if (PaymentRequestHasPurchaseOrders::where('payment_request_id', $paymentRequest->id)->exists()) {
                foreach (PaymentRequestHasPurchaseOrders::where('payment_request_id', $paymentRequest->id)->get() as $paymentRequestHasPurchaseOrders) {
                    if (PurchaseOrder::where([
                        'id' => $paymentRequestHasPurchaseOrders->purchase_order_id,
                        'deleted_at' => null
                    ])->exists()) {
                        foreach (PurchaseOrder::where([
                            'id' => $paymentRequestHasPurchaseOrders->purchase_order_id,
                            'deleted_at' => null
                        ])->get() as $purchaseOrder) {
                            if (SupplyApprovalFlow::where(['id_purchase_order' => $purchaseOrder->id, 'status' => 1])->exists()) {
                                if (PurchaseOrderHasProducts::where('purchase_order_id', $purchaseOrder->id)->exists()) {
                                    foreach (PurchaseOrderHasProducts::where('purchase_order_id', $purchaseOrder->id)->get() as $getProductsInfo) {
                                        if (!PurchaseOrderDelivery::where([
                                            'payment_request_id' => $paymentRequest->id,
                                            'purchase_order_id' => $purchaseOrder->id,
                                            'product_id' => $getProductsInfo->product_id
                                        ])->exists()) {
                                            DB::table('purchase_order_deliverys')->insert([
                                                'payment_request_id' => $paymentRequest->id,
                                                'purchase_order_id' =>  $purchaseOrder->id,
                                                'product_id' => $getProductsInfo->product_id,
                                                'delivery_quantity' => 0,
                                                'quantity' => $getProductsInfo->quantity,
                                                'status' => 0
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

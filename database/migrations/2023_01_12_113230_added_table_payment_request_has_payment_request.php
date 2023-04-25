<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedTablePaymentRequestHasPaymentRequest extends Migration
{
    public function up()
    {
        Schema::create('payment_requests_installments_linked', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('payment_requests_installment_id')->unsigned();
            $table->foreign('payment_requests_installment_id', 'pri_installment_foreign')->references('id')->on('payment_requests_installments')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->integer('payment_request_id')->unsigned();
            $table->foreign('payment_request_id', 'pri_foreign')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->boolean('allow_binding')->default(false);
        });

        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->boolean('linked')->default(false);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_requests_installments_linked');
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('allow_binding');
        });
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->dropColumn('linked');
        });
    }

}

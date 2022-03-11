<?php

use App\Models\PaymentRequestHasInstallments;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedFeesDiscountInstallments extends Migration
{
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->double('fees')->default(0);
            $table->double('discount')->default(0);
            $table->double('initial_value')->default(0);
        });

        foreach(PaymentRequestHasInstallments::get() as $installments){
            $installments->initial_value = $installments->portion_amount;
            $installments->save();
        }

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('fees');
            $table->dropColumn('discount');
            $table->dropColumn('initial_value');
        });
    }

    public function down()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->dropColumn('fees');
            $table->dropColumn('discount');
        });
    }
}

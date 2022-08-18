<?php

use App\Models\PaymentRequestHasInstallments;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedValuePortionAmountInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach(PaymentRequestHasInstallments::get() as $installment){
            if($installment['portion_amount'] == 0){
                $installmentDB = PaymentRequestHasInstallments::findOrFail($installment['id']);
                $installmentDB->portion_amount = $installmentDB->initial_value;
                $installmentDB->save();
            }
        }
    }
}

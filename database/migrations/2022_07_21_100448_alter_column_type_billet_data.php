<?php

use App\Models\PaymentRequestHasInstallments;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnTypeBilletData extends Migration
{
    public function up()
    {
        foreach (PaymentRequestHasInstallments::whereNotNull('bar_code')->get() as $installment) {
            if ((strlen($installment['bar_code']) == 55)) {
                $installmentPayment = PaymentRequestHasInstallments::findOrFail($installment['id']);
                $installmentPayment->type_billet = 1;
                $installmentPayment->save();
            }
            if ((strlen($installment['bar_code']) == 54)) {
                $installmentPayment = PaymentRequestHasInstallments::findOrFail($installment['id']);
                $installmentPayment->type_billet = 0;
                $installmentPayment->save();
            }
        }
    }
}

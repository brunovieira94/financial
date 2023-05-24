<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\FormPayment;

class AlterCodeCnabFormPayment extends Migration
{
    public function up()
    {
        $formPayment = FormPayment::where([
            'code_cnab' => '03',
            'bank_code' => '001',
            'group_form_payment_id' => 4,
            'same_ownership' => true,
        ])->first();

        $formPayment->code_cnab = '01';

        $formPayment->save();
    }
}

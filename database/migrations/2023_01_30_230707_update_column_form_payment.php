<?php

use App\Models\FormPayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnFormPayment extends Migration
{
    public function up()
    {
        if(FormPayment::where('title', 'Boleto')->where('code_cnab', '11')->where('bank_code', '033')->where('same_ownership', true)->exists()){
            $formPayment = FormPayment::where('title', 'Boleto')->where('code_cnab', '11')->where('bank_code', '033')->where('same_ownership', true)->first();
            $formPayment->code_cnab = '30';
            $formPayment->save();
        }
        if(FormPayment::where('title', 'Boleto')->where('code_cnab', '11')->where('bank_code', '033')->where('same_ownership', false)->exists()){
            $formPayment = FormPayment::where('title', 'Boleto')->where('code_cnab', '11')->where('bank_code', '033')->where('same_ownership', false)->first();
            $formPayment->code_cnab = '31';
            $formPayment->save();
        }

    }
}

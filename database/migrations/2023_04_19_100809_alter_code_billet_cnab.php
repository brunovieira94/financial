<?php

use App\Models\FormPayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCodeBilletCnab extends Migration
{
    public function up()
    {
        $formPayment = FormPayment::where([
            'title' => 'Boleto de Outros Bancos',
            'code_cnab' => '11',
            'bank_code' => '341'
        ])->first();

        $formPayment->code_cnab = '31';

        $formPayment->save();
    }

}

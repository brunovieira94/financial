<?php

use App\Models\FormPayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterFormPaymentBankBrazil extends Migration
{
    public function up()
    {
        FormPayment::where('title', 'TED Mesma Titularidade')
            ->where('bank_code', '001')
            ->update([
                'group_form_payment_id' => null,
                'same_ownership' => null
            ]);

        FormPayment::create([
            'title' => 'DOC/TED',
            'code_cnab' => '03',
            'bank_code' => '001',
            'group_form_payment_id' => 4,
            'same_ownership' => 1,
            'concessionaire_billet' => 0
        ]);
    }

}

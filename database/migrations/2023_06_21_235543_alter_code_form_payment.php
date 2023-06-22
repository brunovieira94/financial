<?php

use App\Models\FormPayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCodeFormPayment extends Migration
{
    public function up()
    {
        FormPayment::where('group_form_payment_id', 4)
            ->where([
                'concessionaire_billet' => false,
                'same_ownership' => 1
            ])
            ->update(['code_cnab' => '43']);

        FormPayment::where('group_form_payment_id', 4)
            ->where([
                'concessionaire_billet' => false,
            ])
            ->where('same_ownership', '!=', 1)
            ->update(['code_cnab' => '41']);
    }
}

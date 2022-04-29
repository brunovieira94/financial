<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedFormPaymentInGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('form_payment')
              ->whereIn('title', ['Conta Corrente', 'Poupança', 'Conta Corrente - Outro titular', 'Conta Corrente - Mesmo Titular', 'Conta Poupança'])
              ->update(['group_form_payment_id' => 5]);

        DB::table('form_payment')->insert([
            [
                'title'     => 'Conta Poupança',
                'code_cnab' => '05',
                'bank_code' => '341',
                'group_form_payment_id' => 5,
                'same_ownership' => true
            ],
            [
                'title'     => 'Conta Corrente',
                'code_cnab' => '01',
                'bank_code' => '001',
                'group_form_payment_id' => 5,
                'same_ownership' => true
            ],
            [
                'title'     => 'Poupança',
                'code_cnab' => '05',
                'bank_code' => '001',
                'group_form_payment_id' => 5,
                'same_ownership' => true
            ],
        ]);

    }


}

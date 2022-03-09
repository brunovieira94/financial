<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


class AddedBilletFormPayment extends Migration
{
    public function up()
    {
        DB::table('form_payment')->insert([
            [
                'title'     => 'Boleto',
                'code_cnab' => '00',
                'bank_code' => '001',
                'group_form_payment_id' => 1
            ]
        ]);

        DB::table('form_payment')
              ->whereIn('title', ['DOC/TED'])
              ->update(['group_form_payment_id' => 3]);
    }
}

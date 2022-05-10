<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedFormPaymentCnabBb extends Migration
{
    public function up()
    {
        DB::table('form_payment')->insert([
            [
                'title'     => 'DOC/TED',
                'code_cnab' => '03',
                'bank_code' => '001',
                'group_form_payment_id' => 3,
                'same_ownership' => true
            ],
        ]);
    }
}

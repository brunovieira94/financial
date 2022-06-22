<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemoveFormPaymentCnabBbIsItau extends Migration
{
    public function up()
    {
        DB::table('form_payment')
              ->whereIn('title', ['Conta Poupança', 'Poupança'])
              ->update(['group_form_payment_id' => null]);
    }
}

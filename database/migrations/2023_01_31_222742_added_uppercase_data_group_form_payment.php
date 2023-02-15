<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedUppercaseDataGroupFormPayment extends Migration
{
    public function up()
    {
        DB::statement('UPDATE api.group_form_payment SET title = UPPER(title)');
    }
}

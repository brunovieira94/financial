<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnCnabReturnBilling extends Migration
{
    public function up()
    {
        Schema::table('billing_payments', function (Blueprint $table) {
            $table->string('status_cnab_code')->nullable();
            $table->text('text_cnab')->nullable();
        });
    }
}

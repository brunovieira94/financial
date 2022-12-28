<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMainPaymentTypeColumnInGroupFormPayment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_form_payment', function (Blueprint $table) {
            $table->boolean('main_payment')->nullable();
        });
    }
}

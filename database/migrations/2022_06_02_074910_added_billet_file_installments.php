<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedBilletFileInstallments extends Migration
{
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->string('billet_file')->nullable();
        });
    }
}

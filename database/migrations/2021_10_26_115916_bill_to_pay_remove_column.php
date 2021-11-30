<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BillToPayRemoveColumn extends Migration
{
    public function up()
    {
        Schema::table('bills_to_pay', function (Blueprint $table) {
            $table->dropColumn('tax_amount');
            $table->dropColumn('type_of_tax');
        });
    }

    public function down()
    {
        Schema::table('bills_to_pay', function (Blueprint $table) {
            $table->double('tax_amount')->nullable();
            $table->string('type_of_tax')->nullable();
        });
    }
}

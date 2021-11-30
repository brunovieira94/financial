<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnBillToPay extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bill_to_pay_has_installments', function(Blueprint $table) {
            $table->renameColumn('bill_to_pay', 'id_bill_to_pay');
        });
    }

    public function down()
    {
        Schema::table('bill_to_pay_has_installments', function(Blueprint $table) {
            $table->renameColumn('id_bill_to_pay', 'bill_to_pay');
        });
    }
}

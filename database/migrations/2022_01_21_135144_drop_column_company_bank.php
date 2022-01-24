<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnCompanyBank extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign('bills_to_pay_id_bank_account_company_foreign');
            $table->dropColumn('bank_account_company_id');
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->integer('bank_account_company_id')->unsigned()->nullable();
            $table->foreign('bank_account_company_id')->references('id')->on('payment_requests')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });

    }
}

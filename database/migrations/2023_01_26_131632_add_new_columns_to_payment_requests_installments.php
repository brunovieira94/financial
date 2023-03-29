<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToPaymentRequestsInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->date('payment_made_date')->nullable();
            $table->double('paid_value')->nullable();
            $table->unsignedInteger('bank_account_company_id')->nullable();
            $table->unsignedInteger('group_form_payment_made_id')->nullable();
            $table->integer('system_payment_method')->nullable();
            $table->foreign('bank_account_company_id', 'fk_installment_bank_account_company_id')
                ->references('id')->on('bank_accounts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('group_form_payment_made_id', 'fk_installment_group_form_payment_made')
                ->references('id')->on('group_form_payment')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->dropColumn('payment_made_date');
            $table->dropColumn('paid_value');
            $table->dropColumn('bank_account_company_id');
            $table->dropColumn('group_form_payment_made_id');
            $table->dropColumn('system_payment_method');
        });
    }
}

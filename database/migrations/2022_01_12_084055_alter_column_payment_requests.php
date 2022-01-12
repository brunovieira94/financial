<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterColumnPaymentRequests extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->renameColumn('id_provider', 'provider_id');
            $table->renameColumn('id_bank_account_provider', 'bank_account_provider_id');
            $table->renameColumn('id_bank_account_company', 'bank_account_company_id');
            $table->renameColumn('id_business', 'business_id');
            $table->renameColumn('id_cost_center', 'cost_center_id');
            $table->renameColumn('id_chart_of_account', 'chart_of_account_id');
            $table->renameColumn('id_currency', 'currency_id');
            $table->renameColumn('id_user', 'user_id');
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->renameColumn('provider_id', 'id_provider');
            $table->renameColumn('bank_account_provider_id', 'id_bank_account_provider');
            $table->renameColumn('bank_account_company_id', 'id_bank_account_company');
            $table->renameColumn('business_id', 'id_business');
            $table->renameColumn('cost_center_id', 'id_cost_center');
            $table->renameColumn('chart_of_account_id', 'id_chart_of_account');
            $table->renameColumn('currency_id', 'id_currency');
            $table->renameColumn('user_id', 'id_user');
        });
    }
}

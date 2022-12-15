<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnNotifyApproval extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_account_approval_notification')->default(false);
            $table->boolean('daily_notification_accounts_approval_mail')->default(false);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_account_approval_notification');
            $table->dropColumn('daily_notification_accounts_approval_mail');
        });
    }
}

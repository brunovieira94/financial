<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedColumnsInPaymentRequest extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('or')->nullable();
            $table->string('hash')->nullable();
            $table->text('admin_id')->nullable();
            $table->string('process_number')->nullable();

        });
    }

    public function down()
    {
        Schema::table('payment_requests_installments', function (Blueprint $table) {
            $table->dropColumn('or');
            $table->dropColumn('hash');
            $table->dropColumn('admin_id');
            $table->dropColumn('process_number');
        });
    }
}

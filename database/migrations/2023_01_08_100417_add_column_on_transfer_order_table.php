<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnOnTransferOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('transfer_orders')->truncate();

        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->integer('approve_count')->after('users_ids')->default(0);
        });
    }
}

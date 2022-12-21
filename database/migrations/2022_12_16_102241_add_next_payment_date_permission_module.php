<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNextPaymentDatePermissionModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('module')->insert([
            'title' => 'Data de Pagamento',
            'route' => 'financial',
            'parent' => 3,
            'active' => 1
        ]);
    }
}

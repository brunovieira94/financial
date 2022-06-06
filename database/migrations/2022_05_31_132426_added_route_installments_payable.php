<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedRouteInstallmentsPayable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = Module::where('title', 'Relatórios')->first();

        DB::table('module')->insert([
            [
                'title'     => 'Parcelas a pagar',
                'route' => 'installments-payable',
                'parent' => $module->id,
            ]
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;

class RemoveBillingErrorAndCnabModules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */


    public function up()
    {
        $module = Module::where(['route' => 'billing-cnab-generated'])->first();
        Module::find($module->id)->delete();
        $module = Module::where(['route' => 'billing-error'])->first();
        Module::find($module->id)->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $module = Module::where('title', 'Faturamento de Hotéis')->first();
        DB::table('module')->insert([
            [
                'title'     => 'Faturamento com Erro',
                'route' => 'billing-error',
                'parent' => $module->id,
            ],
            [
                'title'     => 'Faturamento com CNAB Gerado',
                'route' => 'billing-cnab-generated',
                'parent' => $module->id,
            ]
        ]);
    }
}

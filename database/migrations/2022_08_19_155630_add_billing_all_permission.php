<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;

class AddBillingAllPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */


    public function up()
    {
        $module = Module::where('title', 'Faturamento de Hotéis')->first();
        DB::table('module')->insert([
            [
                'title'     => 'Todos Faturamentos',
                'route' => 'billing-all',
                'parent' => $module->id,
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $module = Module::where(['route' => 'billing-all'])->first();
        Module::find($module->id)->delete();
    }
}

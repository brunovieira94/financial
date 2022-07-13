<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;

class AddHotelApprovalFlowToModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('module')->insert([
            [
                'title'     => 'Fluxo de Aprovação de Hotéis',
                'route' => 'hotel-approval-flow',
                'parent' => 40,
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
        $module = Module::where(['route' => 'hotel-approval-flow'])->first();
        Module::find($module->id)->delete();
    }
}

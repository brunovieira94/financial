<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;

class HotelApprovalFlowModuleParentEdit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = Module::where('title', 'Faturamento de Hotéis')->first();
        Module::where('route', 'hotel-approval-flow')->update(['parent' => $module->id]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

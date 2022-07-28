<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;

class EditBillingPermissionsParent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */


    public function up()
    {
        $module = Module::where('title', 'Faturamento de Hotéis')->first();

        Module::where('route', 'billing-open')->update(['parent' => $module->id]);
        Module::where('route', 'billing-approved')->update(['parent' => $module->id]);
        Module::where('route', 'billing-rejected')->update(['parent' => $module->id]);
        Module::where('route', 'billing-canceled')->update(['parent' => $module->id]);
        Module::where('route', 'billing-paid')->update(['parent' => $module->id]);
        Module::where('route', 'billing-error')->update(['parent' => $module->id]);
        Module::where('route', 'billing-cnab-generated')->update(['parent' => $module->id]);
        Module::where('route', 'billing-finished')->update(['parent' => $module->id]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
}

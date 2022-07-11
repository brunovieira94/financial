<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Module;

class EditBillingPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Module::where('route', 'billing/open')->update(['route' => 'billing-open']);
        Module::where('route', 'billing/approved')->update(['route' => 'billing-approved']);
        Module::where('route', 'billing/rejected')->update(['route' => 'billing-rejected']);
        Module::where('route', 'billing/canceled')->update(['route' => 'billing-canceled']);
        Module::where('route', 'billing/paid')->update(['route' => 'billing-paid']);
        Module::where('route', 'billing/error')->update(['route' => 'billing-error']);
        Module::where('route', 'billing/cnab-generated')->update(['route' => 'billing-cnab-generated']);
        Module::where('route', 'billing/finished')->update(['route' => 'billing-finished']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Module::where('route', 'billing-open')->update(['route' => 'billing/open']);
        Module::where('route', 'billing-approved')->update(['route' => 'billing/approved']);
        Module::where('route', 'billing-rejected')->update(['route' => 'billing/rejected']);
        Module::where('route', 'billing-canceled')->update(['route' => 'billing/canceled']);
        Module::where('route', 'billing-paid')->update(['route' => 'billing/paid']);
        Module::where('route', 'billing-error')->update(['route' => 'billing/error']);
        Module::where('route', 'billing-cnab-generated')->update(['route' => 'billing/cnab-generated']);
        Module::where('route', 'billing-finished')->update(['route' => 'billing/finished']);
    }
}

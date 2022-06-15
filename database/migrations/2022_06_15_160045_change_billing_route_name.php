<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;

class ChangeBillingRouteName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Module::where('route', 'hotel-billing')->update(['route' => 'billing']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Module::where('route', 'billing')->update(['route' => 'hotel-billing']);
    }
}

<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedRouteInModule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Module::where('route', '=', 'purchase-request')->exists()){
            Module::create([
                'title' => 'Requisição de compra',
                'parent' => 4,
                'route' => 'purchase-request',
            ]);
        }

    }

    public function down()
    {
        $module = Module::where('route', 'purchase-request')->first();
        Module::find($module->id)->delete();
    }
}

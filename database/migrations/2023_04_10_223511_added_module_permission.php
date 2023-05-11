<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedModulePermission extends Migration
{
    public function up()
    {
        $moduleConfig = Module::where('route', 'configs')->first();
        Module::create([
            'title' => 'Integração',
            'route' => 'integration-system',
            'parent' => $moduleConfig->id,
            'active' => true,
        ]);
    }
}

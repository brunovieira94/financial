<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveIntegrationModule extends Migration
{
    public function up()
    {
        DB::statement("DELETE FROM module WHERE route like '%integration%'");

        $integrationSystem = new Module([
            'title' => 'Integração',
            'route' => 'integration-system',
            'parent' => Module::where('route', 'configs')->first()->id,
        ]);

        $integrationSystem->save();
    }
}

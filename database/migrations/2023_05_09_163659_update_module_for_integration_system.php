<?php

use Illuminate\Database\Migrations\Migration;

use App\Models\Module;

class UpdateModuleForIntegrationSystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // If it exists it will be deleted
        Module::where('route', 'integration')->delete();

        $integrationSystem = new Module([
            'title' => 'Integração',
            'route' => 'integration-system',
            'parent' => Module::where('route', 'configs')->first()->id,
        ]);

        $integrationSystem->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Module::where('route', 'integration-system')->delete();

        $integrationSystem = new Module([
            'title' => 'Integração',
            'route' => 'integration',
            'parent' => Module::where('route', 'configs')->first()->id,
        ]);

        $integrationSystem->save();
    }
}

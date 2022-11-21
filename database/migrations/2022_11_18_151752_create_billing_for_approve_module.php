<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Module;

class CreateBillingForApproveModule extends Migration
{
    public function up()
    {
        $module = Module::where('title', 'Faturamento de Hotéis')->first();
        DB::table('module')->insert([
            [
                'title'     => 'Faturamentos a Aprovar',
                'route' => 'get-billing-for-approve',
                'parent' => $module->id,
            ]
        ]);
    }

    public function down()
    {
        $module = Module::where(['route' => 'get-billing-for-approve'])->first();
        Module::find($module->id)->delete();
    }
}

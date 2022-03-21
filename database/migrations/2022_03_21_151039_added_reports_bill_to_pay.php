<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedReportsBillToPay extends Migration
{
    public function up()
    {
        $module = Module::where('title', 'Relatórios')->first();

        DB::table('module')->insert([
            [
                'title'     => 'Contas a pagar',
                'route' => 'bills-to-pay',
                'parent' => $module->id,
            ]
        ]);
    }
}

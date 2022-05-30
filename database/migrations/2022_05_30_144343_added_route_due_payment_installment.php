<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedRouteDuePaymentInstallment extends Migration
{
    public function up()
    {
        $module = Module::where('title', 'Relatórios')->first();

        DB::table('module')->insert([
            [
                'title'     => 'Parcelas a vencer',
                'route' => 'due-installments',
                'parent' => $module->id,
            ]
        ]);
    }
}

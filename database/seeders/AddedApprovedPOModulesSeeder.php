<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Module;

class AddedApprovedPOModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $reports = Module::where('title', 'Relatórios')->first();
        $modulesReport = [
            [
                'title' => 'Pedidos de Compras Aprovados',
                'route' => 'approved-purchase-order',
                'parent' => $reports->id,
            ],
        ];
        Module::insert($modulesReport);
    }
}

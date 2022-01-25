<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Module;

class AddedModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $financial = Module::where('title', 'Financeiro')->first();
        $modulesFinancial = [
            [
                'title' => 'Contas rejeitadas',
                'route' => 'disapproved-payment-request',
                'parent' => $financial->toArray()['id'],
            ],
        ];

        $reports = Module::where('title', 'Relatórios')->first();
        $modulesReport = [
            [
                'title' => 'Contas deletadas',
                'route' => 'payment-requests-deleted',
                'parent' => $reports->id,
            ],
        ];
        Module::insert($modulesFinancial);
        Module::insert($modulesReport);
    }
}

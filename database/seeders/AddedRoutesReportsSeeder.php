<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class AddedRoutesReportsSeeder extends Seeder
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
                'title' => 'Contas pagas',
                'route' => 'payment-requests-paid',
                'parent' => $reports->id,
            ],
            [
                'title' => 'CNABs gerados',
                'route' => 'payment-requests-cnab-generated',
                'parent' => $reports->id,
            ],
        ];
        Module::insert($modulesReport);
    }
}

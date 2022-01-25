<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Module;

class ModuleReportsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($reports)
    {

        $modules = [
            [
                'title' => 'Contas a vencer',
                'route' => 'due-bills',
                'parent' => $reports->toArray()['id'],
            ],
            [
                'title' => 'Contas aprovadas',
                'route' => 'approved-payment-request',
                'parent' => $reports->toArray()['id'],
            ],
            [
                'title' => 'Contas deletadas',
                'route' => 'payment-requests-deleted',
                'parent' => $reports->toArray()['id'],
            ],
        ];
        Module::insert($modules);
    }
}

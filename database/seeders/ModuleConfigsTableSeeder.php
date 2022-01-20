<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Module;

class ModuleConfigsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($configs)
    {
        $modules = [
            [
                'title' => 'Perfis de Usuário',
                'route' => 'role',
                'parent' => $configs->toArray()['id'],
            ],
            [
                'title' => 'Usuários',
                'route' => 'user',
                'parent' => $configs->toArray()['id'],
            ],
            [
                'title' => 'Fluxo de Aprovação',
                'route' => 'approval-flow',
                'parent' => $configs->toArray()['id'],
            ],
        ];
        Module::insert($modules);
    }
}

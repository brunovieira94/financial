<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Module;

class ModuleSupplysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($supplys)
    {
        $modules = [
            [
                'title' => 'Produtos',
                'route' => 'product',
                'parent' => $supplys->toArray()['id'],
            ],
            [
                'title' => 'Serviços',
                'route' => 'service',
                'parent' => $supplys->toArray()['id'],
            ],
            [
                'title' => 'Pedidos de Compra',
                'route' => 'purchase-order',
                'parent' => $supplys->toArray()['id'],
            ],
            [
                'title' => 'Pedidos a Aprovar',
                'route' => 'supply-approval-flow',
                'parent' => $supplys->toArray()['id'],
            ],
            [
                'title' => 'Fluxo de Aprovação de Suprimentos',
                'route' => 'approval-flow-supply',
                'parent' => $supplys->toArray()['id'],
            ],
        ];
        Module::insert($modules);
    }
}

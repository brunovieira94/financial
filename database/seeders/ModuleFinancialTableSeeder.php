<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Module;

class ModuleFinancialTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($financial)
    {
        $modules = [
            [
                'title' => 'Solicitação de Pagamentos',
                'route' => 'payment-request',
                'parent' => $financial->toArray()['id'],
            ],
            [
                'title' => 'Contas a aprovar',
                'route' => 'account-payable-approval-flow',
                'parent' => $financial->toArray()['id'],
            ],
            [
                'title' => 'Contas rejeitadas',
                'route' => 'disapproved-payment-request',
                'parent' => $financial->toArray()['id'],
            ],
        ];
        Module::insert($modules);
    }
}

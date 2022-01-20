<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Module;

class ModuleRegistrationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($registration)
    {

        $modules = [
            [
                'title' => 'Categoria de Fornecedores',
                'route' => 'provider-category',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Fornecedores',
                'route' => 'provider',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Empresas',
                'route' => 'company',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Negócios',
                'route' => 'business',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Centros de Custo',
                'route' => 'cost-center',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Plano de Contas',
                'route' => 'chart-of-accounts',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Métodos de Pagamento',
                'route' => 'payment-method',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Tipos de Pagamento',
                'route' => 'payment-type',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Tipos de Atributos',
                'route' => 'attribute-type',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Bancos',
                'route' => 'bank',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Moedas',
                'route' => 'currency',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Países',
                'route' => 'country',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Estados',
                'route' => 'state',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Cidades',
                'route' => 'city',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Unidades de Medida',
                'route' => 'measurement-unit',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Impostos',
                'route' => 'type-of-tax',
                'parent' => $registration->toArray()['id'],
            ],
            [
                'title' => 'Motivos para Rejeitar',
                'route' => 'reason-to-reject',
                'parent' => $registration->toArray()['id'],
            ],
        ];
        Module::insert($modules);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Module;

class ModuleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cadastros = Module::create([
            'title' => 'Cadastros',
            'route' => 'cadastros',
        ]);
        $modules = [
            [
                'title' => 'Tipos de Pagamento',
                'route' => 'payment-type',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Métodos de Pagamento',
                'route' => 'payment-method',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Moedas',
                'route' => 'currency',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Centros de Custo',
                'route' => 'cost-center',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Bancos',
                'route' => 'bank',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Plano de Contas',
                'route' => 'chart-of-accounts',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Contas Bancárias',
                'route' => 'bank-account',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Categoria de Fornecedores',
                'route' => 'provider-category',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Modulos',
                'route' => 'module',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Perfis de Acesso',
                'route' => 'role',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Estados',
                'route' => 'state',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Cidades',
                'route' => 'city',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Países',
                'route' => 'country',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Fluxo de Aprovação',
                'route' => 'approval-flow',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Fornecedores',
                'route' => 'provider',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Empresas',
                'route' => 'company',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Negócios',
                'route' => 'business',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Usuários',
                'route' => 'user',
                'parent' => $cadastros->toArray()['id'],
            ],
            [
                'title' => 'Logs',
                'route' => 'logs',
                'parent' => null,
            ],
        ];
        Module::insert($modules);
    }
}

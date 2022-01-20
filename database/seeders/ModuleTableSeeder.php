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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('module')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $registration = Module::create([
            'title' => 'Cadastros',
            'route' => 'cadastros',
        ]);

        $reports = Module::create([
            'title' => 'Relatórios',
            'route' => 'reports',
        ]);
        $financial = Module::create([
            'title' => 'Financeiro',
            'route' => 'financial',
        ]);
        $supplys = Module::create([
            'title' => 'Suprimentos',
            'route' => 'supplys',
        ]);
        $configs = Module::create([
            'title' => 'Configurações',
            'route' => 'configs',
        ]);

        $this->callWith(ModuleRegistrationTableSeeder::class, ['registration' => $registration]);
        $this->callWith(ModuleReportsTableSeeder::class, ['reports' => $reports]);
        $this->callWith(ModuleFinancialTableSeeder::class, ['financial' => $financial]);
        $this->callWith(ModuleConfigsTableSeeder::class, ['configs' => $configs]);
        $this->callWith(ModuleSupplysTableSeeder::class, ['supplys' => $supplys]);
    }
}

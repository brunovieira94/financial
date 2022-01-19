<?php

namespace Database\Seeders;

use App\Models\TypeOfTax;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ModuleTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(StatesTableSeeder::class);
        $this->call(CitiesTableSeeder::class);
        $this->call(TaxSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        // \App\Models\User::factory(10)->create();
    }
}

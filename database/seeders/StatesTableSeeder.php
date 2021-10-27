<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatesTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('states')->delete();

        DB::table('states')->insert([
            ['country_id' => 1, 'id' => 1, 'title' => 'Acre'],
            ['country_id' => 1, 'id' => 2, 'title' => 'Alagoas'],
            ['country_id' => 1, 'id' => 3, 'title' => 'Amapá'],
            ['country_id' => 1, 'id' => 4, 'title' => 'Amazonas'],
            ['country_id' => 1, 'id' => 5, 'title' => 'Bahia'],
            ['country_id' => 1, 'id' => 6, 'title' => 'Ceará'],
            ['country_id' => 1, 'id' => 7, 'title' => 'Distrito Federal'],
            ['country_id' => 1, 'id' => 8, 'title' => 'Espírito Santo'],
            ['country_id' => 1, 'id' => 9, 'title' => 'Goiás'],
            ['country_id' => 1, 'id' => 10, 'title' => 'Maranhão'],
            ['country_id' => 1, 'id' => 11, 'title' => 'Mato Grosso'],
            ['country_id' => 1, 'id' => 12, 'title' => 'Mato Grosso do Sul'],
            ['country_id' => 1, 'id' => 13, 'title' => 'Minas Gerais'],
            ['country_id' => 1, 'id' => 14, 'title' => 'Pará'],
            ['country_id' => 1, 'id' => 15, 'title' => 'Paraíba'],
            ['country_id' => 1, 'id' => 16, 'title' => 'Paraná'],
            ['country_id' => 1, 'id' => 17, 'title' => 'Pernambuco'],
            ['country_id' => 1, 'id' => 18, 'title' => 'Piauí'],
            ['country_id' => 1, 'id' => 19, 'title' => 'Rio de Janeiro'],
            ['country_id' => 1, 'id' => 20, 'title' => 'Rio Grande do Norte'],
            ['country_id' => 1, 'id' => 21, 'title' => 'Rio Grande do Sul'],
            ['country_id' => 1, 'id' => 22, 'title' => 'Rondônia'],
            ['country_id' => 1, 'id' => 23, 'title' => 'Roraima'],
            ['country_id' => 1, 'id' => 24, 'title' => 'Santa Catarina'],
            ['country_id' => 1, 'id' => 25, 'title' => 'São Paulo'],
            ['country_id' => 1, 'id' => 26, 'title' => 'Sergipe'],
            ['country_id' => 1, 'id' => 27, 'title' => 'Tocantins'],
        ]);
    }

}

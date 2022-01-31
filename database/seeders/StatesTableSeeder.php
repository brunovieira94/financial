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
            ['country_id' => 1, 'id' => 1, 'title' => 'Acre', 'uf' => 'AC'],
            ['country_id' => 1, 'id' => 2, 'title' => 'Alagoas', 'uf' => 'AL'],
            ['country_id' => 1, 'id' => 3, 'title' => 'Amapá', 'uf' => 'AP'],
            ['country_id' => 1, 'id' => 4, 'title' => 'Amazonas', 'uf' => 'AM'],
            ['country_id' => 1, 'id' => 5, 'title' => 'Bahia', 'uf' => 'BA'],
            ['country_id' => 1, 'id' => 6, 'title' => 'Ceará', 'uf' => 'CE'],
            ['country_id' => 1, 'id' => 7, 'title' => 'Distrito Federal', 'uf' => 'DF'],
            ['country_id' => 1, 'id' => 8, 'title' => 'Espírito Santo', 'uf' => 'ES'],
            ['country_id' => 1, 'id' => 9, 'title' => 'Goiás', 'uf' => 'GO'],
            ['country_id' => 1, 'id' => 10, 'title' => 'Maranhão', 'uf' => 'MA'],
            ['country_id' => 1, 'id' => 11, 'title' => 'Mato Grosso', 'uf' => 'MT'],
            ['country_id' => 1, 'id' => 12, 'title' => 'Mato Grosso do Sul', 'uf' => 'MS'],
            ['country_id' => 1, 'id' => 13, 'title' => 'Minas Gerais', 'uf' => 'MG'],
            ['country_id' => 1, 'id' => 14, 'title' => 'Pará', 'uf' => 'PA'],
            ['country_id' => 1, 'id' => 15, 'title' => 'Paraíba', 'uf' => 'PB'],
            ['country_id' => 1, 'id' => 16, 'title' => 'Paraná', 'uf' => 'PR'],
            ['country_id' => 1, 'id' => 17, 'title' => 'Pernambuco', 'uf' => 'PE'],
            ['country_id' => 1, 'id' => 18, 'title' => 'Piauí', 'uf' => 'PI'],
            ['country_id' => 1, 'id' => 19, 'title' => 'Rio de Janeiro', 'uf' => 'RJ'],
            ['country_id' => 1, 'id' => 20, 'title' => 'Rio Grande do Norte', 'uf' => 'RN'],
            ['country_id' => 1, 'id' => 21, 'title' => 'Rio Grande do Sul', 'uf' => 'RS'],
            ['country_id' => 1, 'id' => 22, 'title' => 'Rondônia', 'uf' => 'RO'],
            ['country_id' => 1, 'id' => 23, 'title' => 'Roraima', 'uf' => 'RR'],
            ['country_id' => 1, 'id' => 24, 'title' => 'Santa Catarina', 'uf' => 'SC'],
            ['country_id' => 1, 'id' => 25, 'title' => 'São Paulo', 'uf' => 'SP'],
            ['country_id' => 1, 'id' => 26, 'title' => 'Sergipe', 'uf' => 'SE'],
            ['country_id' => 1, 'id' => 27, 'title' => 'Tocantins', 'uf' => 'TO'],
        ]);
    }

}

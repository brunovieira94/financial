<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TypeOfTax;

class TaxSeeder extends Seeder
{
    public function run()
    {
        $tax = [
        [
            'title' => 'ISS',
        ],
        [
            'title' => 'CSLL',
        ],
        [
            'title' => 'ICMS',
        ],
        [
            'title' => 'PIS/PASEP',
        ],
        [
            'title' => 'COFINS',
        ],
        [
            'title' => 'IRRF',
        ],
        [
            'title' => 'INSS',
        ]
    ];
        TypeOfTax::insert($tax);
    }

}

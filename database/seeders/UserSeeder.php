<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->delete();

        DB::table('users')->insert([
			[
                'name' => 'Super Admin',
                'email' => 'suporte@in8.com.br',
                'password' => Hash::make('BuRox2005'),
                'role_id' => '1',
                'phone' => '(31) 3327-6865',
                'extension' => '1',
            ],
        ]);
    }
}

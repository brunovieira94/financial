<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddedHotelPaymentModule extends Migration
{
    public function up()
    {
        DB::table('module')->insert([
            [
                'title'     => 'Faturamento de Hotéis',
                'route' => 'hotel_payment',
                'parent' => null,
            ]
        ]);

        $module = Module::where('title', 'Faturamento de Hotéis')->first();

        DB::table('module')->insert([
            [
                'title'     => 'Hotéis',
                'route' => 'hotel',
                'parent' => $module->id,
            ]
        ]);

        DB::table('module')->insert([
            [
                'title'     => 'Faturamento',
                'route' => 'hotel-billing',
                'parent' => $module->id,
            ]
        ]);
    }
}

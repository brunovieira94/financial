<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedProviderQuotationInModuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $module = Module::where('title', 'Suprimentos')->first();

        DB::table('module')->insert([
            [
                'title' => 'Cotação',
                'route' => 'provider-quotation',
                'parent' => $module->id,
            ]
        ]);
    }
}

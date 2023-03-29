<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedModuleAttachmentFinancial extends Migration
{
    public function up()
    {
        $module = Module::where('title', 'Financeiro')->first();

        DB::table('module')->insert([
            [
                'title'     => 'Anexos',
                'route' => 'attachment',
                'parent' => $module->id,
            ]
        ]);
    }

}

<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNameReports extends Migration
{
    public function up()
    {
        Module::where('title', 'Aprovações por Usuário')->update(
            [
                'title' => 'Ações por Usuário',
            ]
        );
    }
}

<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedRouteReportsApprovalsUser extends Migration
{
    public function up()
    {
        $moduleParent = Module::where('route', 'reports')->where('active', true)->first();
        Module::create(
            [
                'title' => 'Aprovações por Usuário',
                'route' => 'user-approvals-report',
                'parent' => $moduleParent->id,
                'active' => true,
            ]
        );
    }
}

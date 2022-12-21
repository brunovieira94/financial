<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Module;

class RenameModulePaidBillingInfo extends Migration
{
    public function up()
    {

        DB::table('module')
            ->where('route', 'paid-billing-info')
            ->update(['title' => 'Faturamentos Pagos']);
    }

    public function down()
    {
        DB::table('module')
        ->where('route', 'paid-billing-info')
        ->update(['title' => 'Pagamentos']);
    }
}

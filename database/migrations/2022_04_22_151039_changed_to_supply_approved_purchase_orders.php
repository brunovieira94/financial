<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangedToSupplyApprovedPurchaseOrders extends Migration
{
    public function up()
    {
        $module = Module::where('title', 'Suprimentos')->first();
        Module::where('title', 'Pedidos de Compras Aprovados')->update(['parent' => $module->id]);
    }
}

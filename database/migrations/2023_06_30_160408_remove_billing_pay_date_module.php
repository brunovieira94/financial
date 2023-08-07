<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveBillingPayDateModule extends Migration
{
    public function up()
    {
        $mod = Module::where('title', 'Data de Pagamento')->where('route', 'billing-payment-date')->first();
        if (isset($mod)) DB::table('module')->delete($mod->id);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterProvidersFlagGenericProvider extends Migration
{
    public function up()
    {
        DB::statement(
            "UPDATE providers
             SET generic_provider = 1
             WHERE trade_name like '%FORNECEDOR ESTORNO GFIN%' OR trade_name like '%FORNECEDOR ESTORNO GPOC%' OR trade_name like '%FORNECEDOR HOTEIS%' OR trade_name like '%FORNECEDOR MILHAS%' OR trade_name like '%FORNECEDOR PREJUIZOS%';"
        );
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterColumnProviderTrue extends Migration
{
    public function up()
    {
        DB::table('providers')
              ->update(['allows_registration_without_purchase_order' => true]);

              if (Schema::hasColumn('providers', 'allow_registration_without_purchase_order'))
              {
                  Schema::table('providers', function (Blueprint $table)
                  {
                      $table->dropColumn('allow_registration_without_purchase_order');
                  });
              }
    }

    public function down()
    {
        DB::table('providers')
              ->update(['allows_registration_without_purchase_order' => false]);
    }
}

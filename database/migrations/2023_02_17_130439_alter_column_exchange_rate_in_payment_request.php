<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;

class AlterColumnExchangeRateInPaymentRequest extends Migration
{
    public function up()
    {
        if (!Type::hasType('double')) {
            Type::addType('double', FloatType::class);
        }

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->double('exchange_rate', 9, 4)->change();
        });
    }

    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->double('exchange_rate')->change();
        });
    }
}

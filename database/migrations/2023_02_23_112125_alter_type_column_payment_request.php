<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;

class AlterTypeColumnPaymentRequest extends Migration
{
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->decimal('exchange_rate', 10, 5)->change();
        });
    }

    public function down()
    {
        if (!Type::hasType('double')) {
            Type::addType('double', FloatType::class);
        }

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->double('exchange_rate', 9, 4)->change();
        });
    }
}

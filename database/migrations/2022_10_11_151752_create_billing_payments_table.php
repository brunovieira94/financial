<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Module;

class CreateBillingPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('billing_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('pay_date')->nullable();
            $table->string('boleto_value')->nullable();
            $table->string('boleto_code')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('oracle_protocol')->nullable();
            $table->string('cnpj')->nullable();
            $table->integer('form_of_payment')->nullable();
            $table->integer('status')->default(0);
            $table->string('hotel_id')->nullable();
            $table->foreign('hotel_id')->references('id_hotel_cangooroo')->on('hotels')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
        $module = Module::where('title', 'Faturamento de Hotéis')->first();
        DB::table('module')->insert([
            [
                'title'     => 'Pagamentos',
                'route' => 'billing-payment',
                'parent' => $module->id,
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('billing_payments');
        $module = Module::where(['route' => 'billing-payment'])->first();
        Module::find($module->id)->delete();
    }
}
